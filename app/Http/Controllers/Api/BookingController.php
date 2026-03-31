<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Hàm phụ trợ: Lấy ID khách sạn của đối tác đang đăng nhập
    private function getHotelId($user)
    {
        $hotel = Hotel::where('partner_id', $user->id)->first();
        return $hotel ? $hotel->id : null;
    }

    // Use Case 1: Xem danh sách đơn hàng
    public function index(Request $request)
    {
        $hotelId = $this->getHotelId($request->user());
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        // Lấy danh sách đơn đặt phòng của khách sạn này
        $bookings = Booking::where('hotel_id', $hotelId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Lấy danh sách đơn hàng thành công',
            'bookings' => $bookings
        ], 200);
    }

    // Use Case 1 (tiếp): Xem chi tiết 1 đơn hàng cụ thể
    public function show(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());

        $booking = Booking::with(['details.roomType', 'payment'])
            ->where('id', $id)
            ->where('hotel_id', $hotelId)
            ->first();

        if (!$booking) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        return response()->json([
            'message' => 'Lấy chi tiết đơn hàng thành công',
            'booking' => $booking
        ], 200);
    }

    // Use Case 2: Tra cứu thông tin thanh toán
    public function getPaymentInfo(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());

        $booking = Booking::with('payment')
            ->where('id', $id)
            ->where('hotel_id', $hotelId)
            ->first();

        if (!$booking || !$booking->payment) {
            return response()->json(['message' => 'Không tìm thấy thông tin thanh toán'], 404);
        }

        return response()->json([
            'message' => 'Tra cứu thanh toán thành công',
            'payment' => $booking->payment
        ], 200);
    }

    // Use Case 3: Xác nhận đơn hàng (Chuyển status -> 1)
    public function confirmBooking(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());
        $booking = Booking::where('id', $id)->where('hotel_id', $hotelId)->first();

        if (!$booking) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        $booking->update(['status' => 1]); // 1: Confirmed

        return response()->json(['message' => 'Đã xác nhận đơn hàng thành công', 'booking' => $booking], 200);
    }

    // Use Case 4: Xử lý nhận phòng (Chuyển status -> 2)
    public function checkInBooking(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());
        $booking = Booking::where('id', $id)->where('hotel_id', $hotelId)->first();

        if (!$booking) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        $booking->update(['status' => 2]); // 2: Checked-in

        return response()->json(['message' => 'Đã xử lý nhận phòng thành công', 'booking' => $booking], 200);
    }

    // Use Case 5: Hủy đơn đặt hàng (Chuyển status -> 3)
    public function cancelBooking(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());
        $booking = Booking::where('id', $id)->where('hotel_id', $hotelId)->first();

        if (!$booking) return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);

        $booking->update(['status' => 3]); // 3: Cancelled

        return response()->json(['message' => 'Đã hủy đơn hàng thành công', 'booking' => $booking], 200);
    }
}