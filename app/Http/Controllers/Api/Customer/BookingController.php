<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\BookingDetail;
use App\Models\RoomInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|max:20',
            'guest_email' => 'required|email',
            'total_price' => 'required|numeric'
        ]);

        $room = RoomType::find($request->room_id);

        if (!$room) {
            return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);
        }

        $checkInDate = Carbon::parse($request->check_in);
        $checkOutDate = Carbon::parse($request->check_out);
        $nights = $checkInDate->diffInDays($checkOutDate);

        if ($nights <= 0) {
            $nights = 1;
        }

        $calculatedTotal = $room->base_price * $nights;
        $bookingCode = 'SB-' . strtoupper(Str::random(7));

        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'customer_id' => auth('customer')->id(),
            'hotel_id' => $room->hotel_id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'guest_name' => $request->guest_name,
            'guest_phone' => $request->guest_phone,
            'guest_email' => $request->guest_email,
            'note' => $request->note,

            'total_price' => $calculatedTotal,
            'total_amount' => $calculatedTotal,
            'room_type_id' => $request->room_id,

            'status' => 0,
        ]);

        return response()->json([
            'message' => 'Đặt phòng thành công!',
            'data' => $booking
        ], 201);
    }

    public function createBooking(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guest_name' => 'required|string',
            'guest_phone' => 'required|string',
            'guest_email' => 'required|email',
            'rooms_count' => 'required|integer|min:1',
            'note' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*.id' => 'required_with:services|integer',
            'services.*.quantity' => 'required_with:services|integer|min:1',
            'global_promotion_code' => 'nullable|string', // Mã Sàn
            'hotel_promotion_code' => 'nullable|string'   // Mã KS
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $customerId = auth('customer')->id();
                $roomType = RoomType::find($request->room_type_id);
                if (!$roomType) throw new \Exception('Không tìm thấy loại phòng hợp lệ.');

                // =====================================
                // 👉 ĐÃ SỬA: TÍNH TIỀN PHÒNG ĐỘNG THEO LỊCH KHO PHÒNG
                // =====================================
                $checkIn = Carbon::parse($request->check_in);
                $checkOut = Carbon::parse($request->check_out);
                $nights = $checkIn->diffInDays($checkOut);
                if ($nights <= 0) $nights = 1;

                $subtotal = 0;
                $currentDate = $checkIn->copy();

                for ($i = 0; $i < $nights; $i++) {
                    $dateStr = $currentDate->format('Y-m-d');

                    // Truy quét lịch cấu hình cụ thể từng ngày
                    $inventory = RoomInventory::where('room_type_id', $roomType->id)
                        ->where('apply_date', $dateStr)
                        ->first();

                    // Chặn tức thì nếu ngày này chủ phòng chọn "Đóng bán" (is_closed = 1)
                    if ($inventory && (int)$inventory->is_closed === 1) {
                        throw new \Exception("Rất tiếc, loại phòng này đã dừng nhận khách vào ngày " . $currentDate->format('d/m/Y'));
                    }

                    // Tự động sử dụng giá động theo ngày hoặc rollback về base_price nếu chưa setup
                    $dailyPrice = $inventory ? $inventory->price : $roomType->base_price;
                    $subtotal += $dailyPrice;

                    $currentDate->addDay();
                }

                // Nhân tổng lũy kế số đêm với số lượng phòng khách đặt
                $subtotal = $subtotal * $request->rooms_count;
                $tax = $subtotal * 0.1;

                // =====================================
                // XỬ LÝ KHUYẾN MÃI KÉP (CASCADING)
                // =====================================
                $globalDiscount = 0;
                $hotelDiscount = 0;
                $globalPromoId = null;
                $hotelPromoId = null;

                // 1. ÁP DỤNG MÃ SÀN (GLOBAL)
                if ($request->has('global_promotion_code') && !empty($request->global_promotion_code)) {
                    $promo = \App\Models\Promotion::where('code', $request->global_promotion_code)
                        ->whereNull('hotel_id')->lockForUpdate()->first();

                    if ($promo && $promo->status == 1 && $promo->used_count < ($promo->usage_limit ?? 999999999)) {
                        $globalPromoId = $promo->id;
                        if ($promo->discount_type == 1) {
                            $globalDiscount = $subtotal * ($promo->discount_value / 100);
                            if ($promo->max_discount_amount) $globalDiscount = min($globalDiscount, $promo->max_discount_amount);
                        } else {
                            $globalDiscount = $promo->discount_value;
                        }
                        $globalDiscount = min($globalDiscount, $subtotal);
                        $promo->increment('used_count');
                    }
                }

                // 2. ÁP DỤNG MÃ KHÁCH SÀN (HOTEL) TRÊN GIÁ ĐÃ GIẢM
                $subtotalAfterGlobal = $subtotal - $globalDiscount;

                if ($request->has('hotel_promotion_code') && !empty($request->hotel_promotion_code)) {
                    $promo = \App\Models\Promotion::where('code', $request->hotel_promotion_code)
                        ->where('hotel_id', $request->hotel_id)->lockForUpdate()->first();

                    if ($promo && $promo->status == 1 && $promo->used_count < ($promo->usage_limit ?? 999999999)) {
                        // Check đơn tối thiểu dựa trên giá ĐÃ TRỪ mã sàn
                        if ($promo->min_booking_value == 0 || $subtotalAfterGlobal >= $promo->min_booking_value) {
                            $hotelPromoId = $promo->id;
                            if ($promo->discount_type == 1) {
                                $hotelDiscount = $subtotalAfterGlobal * ($promo->discount_value / 100);
                                if ($promo->max_discount_amount) $hotelDiscount = min($hotelDiscount, $promo->max_discount_amount);
                            } else {
                                $hotelDiscount = $promo->discount_value;
                            }
                            $hotelDiscount = min($hotelDiscount, $subtotalAfterGlobal);
                            $promo->increment('used_count');
                        }
                    }
                }

                $totalDiscount = $globalDiscount + $hotelDiscount;

                // =====================================
                // TÍNH DỊCH VỤ VÀ LƯU ĐƠN
                // =====================================
                $servicesTotal = 0;
                $requestedServices = [];
                if ($request->has('services') && count($request->services) > 0) {
                    $requestedServices = \App\Models\Service::whereIn('id', array_column($request->services, 'id'))->get();
                    foreach ($requestedServices as $srv) {
                        $qty = collect($request->services)->firstWhere('id', $srv->id)['quantity'] ?? 1;
                        $servicesTotal += ($srv->price * $qty);
                    }
                }

                $totalAmount = $subtotal - $totalDiscount + $tax + $servicesTotal;

                $booking = Booking::create([
                    'booking_code' => 'SB-' . strtoupper(Str::random(8)),
                    'customer_id' => $customerId,
                    'hotel_id' => $request->hotel_id,
                    'promotion_id' => $globalPromoId,         // Lưu mã sàn
                    'hotel_promotion_id' => $hotelPromoId,    // Lưu mã KS

                    'guest_name' => $request->guest_name,
                    'guest_phone' => $request->guest_phone,
                    'guest_email' => $request->guest_email,
                    'note' => $request->note,
                    'check_in' => $request->check_in,
                    'check_out' => $request->check_out,

                    'total_amount' => $subtotal + $servicesTotal,
                    'total_price' => $totalAmount,
                    'discount_amount' => $totalDiscount,
                    'vat_amount' => $tax,
                    'status' => 0,
                    'payment_status' => 0,
                ]);

                BookingDetail::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $request->room_type_id,
                    'check_in_date' => $request->check_in,
                    'check_out_date' => $request->check_out,
                    'rooms_count' => $request->rooms_count,
                    'subtotal' => $subtotal
                ]);

                if (count($requestedServices) > 0) {
                    foreach ($requestedServices as $srv) {
                        $qty = collect($request->services)->firstWhere('id', $srv->id)['quantity'] ?? 1;
                        \App\Models\BookingService::create([
                            'booking_id' => $booking->id,
                            'service_id' => $srv->id,
                            'quantity' => $qty,
                            'price_at_booking' => $srv->price,
                            'created_at' => now()
                        ]);
                    }
                }

                return $booking;
            });

            return response()->json([
                'message' => 'Đặt phòng thành công.',
                'booking_code' => $result->booking_code
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with([
            'details.roomType',
            'bookingServices.service',
            'surcharges.category',
            'supply_incidents.supply',
            'review.images',
            'promotion',
            'hotelPromotion'
        ])
            ->where('customer_id', auth('customer')->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Lấy danh sách đơn hàng thành công',
            'data' => $bookings
        ], 200);
    }

    public function cancelMyBooking(Request $request, int $id)
    {
        $booking = Booking::where('id', $id)->where('customer_id', auth('customer')->id())->first();

        if (!$booking) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        if ($booking->status !== 0) {
            return response()->json(['message' => 'Đơn hàng đã được xử lý, không thể hủy!'], 400);
        }

        $booking->update(['status' => 4]);

        return response()->json(['message' => 'Đã hủy đơn hàng thành công', 'booking' => $booking], 200);
    }

    public function getHotelServices(int $hotelId)
    {
        $services = \App\Models\Service::where('hotel_id', $hotelId)
            ->where('type', 1)
            ->where('status', 1)
            ->get();

        return response()->json([
            'message' => 'Lấy danh sách dịch vụ thành công',
            'data' => $services
        ], 200);
    }
}
