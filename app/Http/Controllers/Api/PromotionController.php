<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PromotionController extends Controller
{
    // Hàm phụ trợ: Lấy ID khách sạn của đối tác
    private function getHotelId($user)
    {
        $hotel = Hotel::where('partner_id', $user->id)->first();
        return $hotel ? $hotel->id : null;
    }

    // Use Case 1: Xem danh sách khuyến mãi
    public function index(Request $request)
    {
        $hotelId = $this->getHotelId($request->user());
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $promotions = Promotion::where('hotel_id', $hotelId)->get();

        return response()->json([
            'message' => 'Lấy danh sách khuyến mãi thành công',
            'promotions' => $promotions
        ], 200);
    }

    // Use Case 2: Tạo mã khuyến mãi mới
    public function store(Request $request)
    {
        $hotelId = $this->getHotelId($request->user());
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $request->validate([
            'code' => 'required|string|max:50|unique:promotions,code',
            'discount_type' => 'required|integer|in:1,2', // 1: Phần trăm, 2: Số tiền cố định
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $promotion = Promotion::create([
            'hotel_id' => $hotelId,
            'code' => strtoupper($request->code), // Viết hoa mã cho đồng bộ
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'message' => 'Tạo khuyến mãi thành công',
            'promotion' => $promotion
        ], 201);
    }

    // Use Case 3: Cập nhật khuyến mãi
    public function update(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());
        $promotion = Promotion::where('id', $id)->where('hotel_id', $hotelId)->first();

        if (!$promotion) return response()->json(['message' => 'Không tìm thấy khuyến mãi'], 404);

        $request->validate([
            'discount_type' => 'required|integer|in:1,2',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $promotion->update([
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'message' => 'Cập nhật khuyến mãi thành công',
            'promotion' => $promotion
        ], 200);
    }

    // Use Case 4: Kết thúc sớm khuyến mãi (Đổi ngày kết thúc thành hôm nay)
    public function endEarly(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());
        $promotion = Promotion::where('id', $id)->where('hotel_id', $hotelId)->first();

        if (!$promotion) return response()->json(['message' => 'Không tìm thấy khuyến mãi'], 404);

        // Đặt ngày kết thúc là hôm qua để mã hết hạn ngay lập tức
        $promotion->update([
            'end_date' => Carbon::yesterday()->toDateString() 
        ]);

        return response()->json([
            'message' => 'Đã kết thúc sớm khuyến mãi',
            'promotion' => $promotion
        ], 200);
    }

    // Use Case 5: Xem thống kê sử dụng mã
    public function stats(Request $request, $id)
    {
        $hotelId = $this->getHotelId($request->user());
        $promotion = Promotion::withCount('bookings') // Tự động đếm số lượng đơn hàng dùng mã này
            ->where('id', $id)
            ->where('hotel_id', $hotelId)
            ->first();

        if (!$promotion) return response()->json(['message' => 'Không tìm thấy khuyến mãi'], 404);

        return response()->json([
            'message' => 'Lấy thống kê thành công',
            'promotion_code' => $promotion->code,
            'total_used' => $promotion->bookings_count // Số lượt đã sử dụng
        ], 200);
    }
}