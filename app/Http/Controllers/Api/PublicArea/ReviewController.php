<?php

namespace App\Http\Controllers\Api\PublicArea;

use App\Http\Controllers\Controller;
use App\Models\Review;

class ReviewController extends Controller
{
    /**
     * API: Lấy danh sách đánh giá của 1 khách sạn (Public)
     */
    public function index(int $hotel_id)
    {
        // Lấy đánh giá có status = 1 (Được phép hiển thị)
        // Kèm theo ảnh của đánh giá đó (mối quan hệ 'images')
        $reviews = Review::with(['images', 'customer'])
            ->where('hotel_id', $hotel_id)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }
}
