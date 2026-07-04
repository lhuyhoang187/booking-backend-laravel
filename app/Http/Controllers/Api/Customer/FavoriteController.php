<?php

namespace App\Http\Controllers\Api\Customer; // ĐÃ CẬP NHẬT: Trỏ vào đúng thư mục Customer

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    // API 1: Lấy danh sách khách sạn yêu thích của khách hàng
    public function getFavorites(Request $request)
    {
        $user = $request->user();

        // Lấy danh sách khách sạn kèm theo ảnh thu nhỏ của nó
        $favorites = $user->favoriteHotels()->with(['images'])->get();

        return response()->json([
            'message' => 'Lấy danh sách yêu thích thành công',
            'data' => $favorites
        ], 200);
    }

    // API 2: Bấm nút Trái tim (Thêm vào hoặc Bỏ ra khỏi danh sách)
    public function toggleFavorite(Request $request, int $hotelId)
    {
        $customerId = $request->user()->id;

        // Kiểm tra xem đã thích khách sạn này chưa
        $exists = DB::table('favorites')
            ->where('customer_id', $customerId)
            ->where('hotel_id', $hotelId)
            ->first();

        if ($exists) {
            // Nếu đã thích rồi -> Bấm lại là BỎ THÍCH (Xóa)
            DB::table('favorites')
                ->where('customer_id', $customerId)
                ->where('hotel_id', $hotelId)
                ->delete();

            return response()->json(['message' => 'Đã bỏ yêu thích khách sạn', 'is_favorite' => false], 200);
        } else {
            // Nếu chưa thích -> THÊM VÀO
            DB::table('favorites')->insert([
                'customer_id' => $customerId,
                'hotel_id' => $hotelId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['message' => 'Đã thêm vào danh sách yêu thích', 'is_favorite' => true], 200);
        }
    }
}