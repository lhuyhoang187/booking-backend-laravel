<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    // Hàm phụ trợ: Lấy khách sạn của đối tác đang đăng nhập
    private function getCurrentHotel($user)
    {
        return Hotel::where('partner_id', $user->id)->first();
    }

    // Use Case 1: Xem danh sách loại phòng
    public function index(Request $request)
    {
        $hotel = $this->getCurrentHotel($request->user());
        if (!$hotel) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        // Lấy danh sách phòng kèm theo tiện ích của phòng đó
        $roomTypes = RoomType::with('amenities')->where('hotel_id', $hotel->id)->get();

        return response()->json([
            'message' => 'Lấy danh sách loại phòng thành công',
            'room_types' => $roomTypes
        ], 200);
    }

    // Use Case 2: Thêm loại phòng
    public function store(Request $request)
    {
        $hotel = $this->getCurrentHotel($request->user());
        if (!$hotel) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $request->validate([
            'name' => 'required|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'required|integer|min:0',
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => $request->name,
            'base_price' => $request->base_price,
            'max_adults' => $request->max_adults,
            'max_children' => $request->max_children,
            // Cột slug sẽ được Model tự động tạo nhờ hàm boot() chúng ta đã viết
        ]);

        return response()->json([
            'message' => 'Thêm loại phòng thành công',
            'room_type' => $roomType
        ], 201);
    }

    // Use Case 3: Cập nhật thông tin loại phòng
    public function update(Request $request, $id)
    {
        $hotel = $this->getCurrentHotel($request->user());
        if (!$hotel) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomType = RoomType::where('id', $id)->where('hotel_id', $hotel->id)->first();
        if (!$roomType) return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);

        $request->validate([
            'name' => 'required|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'required|integer|min:0',
        ]);

        $roomType->update([
            'name' => $request->name,
            'base_price' => $request->base_price,
            'max_adults' => $request->max_adults,
            'max_children' => $request->max_children,
        ]);

        return response()->json([
            'message' => 'Cập nhật loại phòng thành công',
            'room_type' => $roomType
        ], 200);
    }

    // Use Case 4: Cập nhật tiện ích cho loại phòng (Ví dụ: Bồn tắm, View biển...)
    public function updateAmenities(Request $request, $id)
    {
        $hotel = $this->getCurrentHotel($request->user());
        if (!$hotel) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomType = RoomType::where('id', $id)->where('hotel_id', $hotel->id)->first();
        if (!$roomType) return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);

        $request->validate([
            'amenity_ids' => 'required|array',
            'amenity_ids.*' => 'integer|exists:amenities,id'
        ]);

        // Cập nhật bảng trung gian room_type_amenity
        $roomType->amenities()->sync($request->amenity_ids);

        return response()->json([
            'message' => 'Cập nhật tiện ích phòng thành công',
            'amenities' => $roomType->amenities
        ], 200);
    }

    // Use Case 5: Xóa/Vô hiệu hóa loại phòng
    public function destroy(Request $request, $id)
    {
        $hotel = $this->getCurrentHotel($request->user());
        if (!$hotel) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomType = RoomType::where('id', $id)->where('hotel_id', $hotel->id)->first();
        if (!$roomType) return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);

        $roomType->delete();

        return response()->json(['message' => 'Đã xóa loại phòng thành công'], 200);
    }
}