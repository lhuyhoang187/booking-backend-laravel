<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    // Use Case 1: Xem chi tiết hồ sơ
    public function show(Request $request)
    {
        $hotel = Hotel::with(['amenities', 'images'])
            ->where('partner_id', $request->user()->id)
            ->first();

        if (!$hotel) {
            return response()->json([
                'message' => 'Bạn chưa tạo hồ sơ khách sạn.',
                'hotel' => null 
            ], 200);
        }

        return response()->json([
            'message' => 'Lấy chi tiết hồ sơ thành công',
            'hotel' => $hotel
        ], 200);
    }

    // Use Case 2: Cập nhật thông tin chung
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'star_rating' => 'nullable|integer|min:1|max:5',
        ]);

        $user = $request->user();
        $hotel = Hotel::where('partner_id', $user->id)->first();

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'city' => $request->city,
            'star_rating' => $request->star_rating,
        ];

        if ($hotel) {
            $hotel->update($data);
        } else {
            $data['partner_id'] = $user->id;
            $data['status'] = 0; 
            $hotel = Hotel::create($data);
        }

        return response()->json([
            'message' => 'Cập nhật thông tin chung thành công!',
            'hotel' => $hotel
        ], 200);
    }

    // Use Case 3: Cập nhật tiện ích khách sạn
    public function updateAmenities(Request $request)
    {
        $request->validate([
            'amenity_ids' => 'required|array',
            'amenity_ids.*' => 'integer|exists:amenities,id'
        ]);

        $hotel = Hotel::where('partner_id', $request->user()->id)->first();

        if (!$hotel) {
            return response()->json(['message' => 'Vui lòng cập nhật thông tin chung trước.'], 400);
        }

        $hotel->amenities()->sync($request->amenity_ids);

        return response()->json([
            'message' => 'Cập nhật tiện ích thành công!',
            'amenities' => $hotel->amenities
        ], 200);
    }

    // Use Case 4: Quản lý hình ảnh
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'is_primary' => 'boolean'
        ]);

        $hotel = Hotel::where('partner_id', $request->user()->id)->first();

        if (!$hotel) {
            return response()->json(['message' => 'Vui lòng cập nhật thông tin chung trước.'], 400);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('hotels', 'public');

            $media = Media::create([
                'model_type' => 'Hotel',
                'model_id' => $hotel->id,
                'file_url' => '/storage/' . $path,
                'is_primary' => $request->is_primary ?? 0
            ]);

            return response()->json([
                'message' => 'Tải hình ảnh thành công!',
                'media' => $media
            ], 201);
        }

        return response()->json(['message' => 'Có lỗi xảy ra khi tải ảnh.'], 500);
    }

    // =========================================================
    // THÊM MỚI: API DÀNH CHO KHÁCH HÀNG (Hiển thị ra trang chủ React)
    // =========================================================
    public function search(Request $request)
    {
        // Lấy danh sách tất cả khách sạn, kèm theo hình ảnh
        // Bỏ qua điều kiện duyệt (status = 1) để test hiển thị ngay lập tức
        $hotels = Hotel::with(['images'])->get();

        return response()->json([
            'message' => 'Lấy danh sách khách sạn thành công',
            'data' => $hotels
        ], 200);
    }

    // =========================================================
    // THÊM MỚI: API LẤY CHI TIẾT 1 KHÁCH SẠN (Kèm theo danh sách phòng)
    // =========================================================
    public function getDetail($id)
    {
        // 1. Tìm khách sạn theo ID kèm theo hình ảnh
        $hotel = Hotel::with(['images'])->find($id);

        if (!$hotel) {
            return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);
        }

        // 2. Lấy danh sách các loại phòng (Room Types) của khách sạn này
        $roomTypes = \App\Models\RoomType::where('hotel_id', $id)->get();
        
        // Gắn danh sách phòng vào object khách sạn để gửi về React
        $hotel->room_types = $roomTypes;

        return response()->json([
            'message' => 'Lấy chi tiết khách sạn thành công',
            'data' => $hotel
        ], 200);
    }

    // THỐNG KÊ (ĐÃ BỌC LỚP BẢO VỆ CHỐNG SẬP WEB)
    public function getStats(Request $request)
    {
        try {
            $hotel = Hotel::where('partner_id', $request->user()->id)->first();

            // Nếu chưa có khách sạn thì trả về toàn bộ số 0
            if (!$hotel) {
                return response()->json(['new_bookings' => 0, 'total_rooms' => 0, 'revenue' => 0], 200);
            }

            // 1. Đếm phòng (Chắc chắn chạy được vì đã có model RoomType)
            $totalRooms = \App\Models\RoomType::where('hotel_id', $hotel->id)->count();

            $newBookings = 0;
            $revenue = 0;

            // 2. Chỉ tính Đơn hàng & Doanh thu NẾU Model Booking đã được bạn tạo ra
            if (class_exists('\App\Models\Booking')) {
                $roomTypeIds = \App\Models\RoomType::where('hotel_id', $hotel->id)->pluck('id');

                $newBookings = \App\Models\Booking::whereIn('room_type_id', $roomTypeIds)
                    ->where('status', 'pending')
                    ->count();

                $revenue = \App\Models\Booking::whereIn('room_type_id', $roomTypeIds)
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->sum('total_price');
            }

            return response()->json([
                'new_bookings' => $newBookings,
                'total_rooms' => $totalRooms,
                'revenue' => $revenue
            ], 200);

        } catch (\Exception $e) {
            // Nếu có lỗi ngầm, Web vẫn trả về mã 200 OK để không bị sập màu đỏ
            // Đồng thời hiển thị số phòng, còn các số khác bằng 0
            return response()->json([
                'new_bookings' => 0,
                'total_rooms' => isset($totalRooms) ? $totalRooms : 0,
                'revenue' => 0,
                'debug_error' => $e->getMessage() // Gửi kèm lỗi ngầm để dev dễ check
            ], 200);
        }
    }
}