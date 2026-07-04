<?php

namespace App\Http\Controllers\Api\PublicArea;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    // ==========================================
    // Dành cho khách hàng xem chi tiết phòng (React Checkout)
    // ==========================================
    public function show(int $id)
    {
        $roomType = RoomType::with(['amenities', 'hotel', 'media'])->find($id);

        if (!$roomType) {
            return response()->json(['message' => 'Không tìm thấy thông tin phòng'], 404);
        }

        return response()->json([
            'message' => 'Lấy thông tin phòng thành công',
            'data' => $roomType
        ], 200);
    }
}