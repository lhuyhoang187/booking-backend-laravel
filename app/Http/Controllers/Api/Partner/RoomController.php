<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Amenity;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function getRoomAmenities(Request $request)
    {
        $amenities = Amenity::where('type', 2)->get();
        return response()->json(['data' => $amenities], 200);
    }

    public function getRooms(Request $request)
    {
        // 👉 GỌI HÀM THÔNG MINH TỪ CONTROLLER GỐC
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin'], 400);

        $rooms = Room::with('roomType')
            ->where('hotel_id', $hotelId)
            ->orderBy('room_name', 'asc')
            ->get();

        return response()->json(['data' => $rooms], 200);
    }

    public function storeRoom(Request $request)
    {
        // 👉 GỌI HÀM THÔNG MINH
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'room_name' => 'required|string|max:50',
        ]);

        $existingRoom = Room::where('hotel_id', $hotelId)
            ->where('room_name', $request->room_name)
            ->first();

        if ($existingRoom) {
            if ($existingRoom->status != 0) {
                return response()->json(['message' => 'Số phòng này đã tồn tại trên sơ đồ!'], 400);
            } else {
                $existingRoom->room_type_id = $request->room_type_id;
                $existingRoom->status = 1;
                $existingRoom->save();

                return response()->json(['message' => 'Phòng này từng bị vô hiệu hóa. Hệ thống đã khôi phục và cập nhật thành công!'], 201);
            }
        }

        $room = new Room();
        $room->hotel_id = $hotelId;
        $room->room_type_id = $request->room_type_id;
        $room->room_name = $request->room_name;
        $room->status = $request->status ?? 1;
        $room->save();

        return response()->json(['message' => 'Thêm số phòng vật lý thành công!'], 201);
    }

    public function updateRoom(Request $request, int $id)
    {
        // 👉 GỌI HÀM THÔNG MINH
        $hotelId = $this->getHotelId();

        $room = Room::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$room) return response()->json(['message' => 'Không tìm thấy phòng'], 404);

        if ($request->has('room_name')) {
            $existingRoom = Room::where('hotel_id', $hotelId)
                ->where('room_name', $request->room_name)
                ->where('id', '!=', $id)
                ->first();

            if ($existingRoom) {
                if ($existingRoom->status != 0) {
                    return response()->json(['message' => 'Số phòng này đã bị trùng với một phòng đang hoạt động!'], 400);
                } else {
                    return response()->json(['message' => 'Số phòng này trùng với một phòng cũ đang bị ẩn. Vui lòng chọn số khác!'], 400);
                }
            }
            $room->room_name = $request->room_name;
        }

        if ($request->has('room_type_id')) $room->room_type_id = $request->room_type_id;
        if ($request->has('status')) $room->status = $request->status;

        $room->save();
        return response()->json(['message' => 'Cập nhật thông tin phòng thành công!'], 200);
    }

    public function deleteRoom(int $id)
    {
        // 👉 GỌI HÀM THÔNG MINH
        $hotelId = $this->getHotelId();

        $room = Room::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$room) return response()->json(['message' => 'Không tìm thấy phòng'], 404);

        if ($room->status == 2) return response()->json(['message' => 'Không thể xóa phòng đang có khách ở!'], 400);
        $room->delete();
        return response()->json(['message' => 'Đã xóa phòng khỏi hệ thống!'], 200);
    }

    // API Lấy danh sách phòng trống theo Hạng phòng
    public function getAvailableRoomsByType(int $roomTypeId)
    {
        try {
            $rooms = \App\Models\Room::where('room_type_id', $roomTypeId)
                ->where('status', 1) // Chỉ lấy phòng Trống (1)
                ->get();
            return response()->json($rooms, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}
