<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PromotionController extends Controller
{
    // Lấy danh sách mã TOÀN SÀN (hotel_id = null)
    public function index()
    {
        // Lấy các mã do Admin tạo
        $promotions = Promotion::whereNull('hotel_id')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Lấy danh sách khuyến mãi toàn sàn thành công',
            'promotions' => $promotions
        ], 200);
    }

    // Tạo mã mới toàn sàn
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:promotions,code',
            'discount_type' => 'required|integer|in:1,2',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_booking_value' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => 'nullable|integer|in:0,1'
        ]);

        $promotion = Promotion::create([
            'hotel_id' => null, // ĐIỂM QUAN TRỌNG NHẤT: Bằng null nghĩa là mã Toàn Sàn
            'code' => strtoupper($request->code),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'max_discount_amount' => $request->max_discount_amount,
            'min_booking_value' => $request->min_booking_value ?? 0,
            'start_date' => date('Y-m-d H:i:s', strtotime($request->start_date)),
            'end_date' => date('Y-m-d H:i:s', strtotime($request->end_date)),
            'usage_limit' => $request->usage_limit,
            'usage_limit_per_user' =>  $request->usage_limit_per_user ?? 1,
            'status' => $request->status ?? 1,
        ]);

        return response()->json(['message' => 'Tạo mã khuyến mãi toàn sàn thành công!', 'promotion' => $promotion], 201);
    }

    // Cập nhật mã
    public function update(Request $request, int $id)
    {
        $promotion = Promotion::whereNull('hotel_id')->where('id', $id)->first();
        if (!$promotion) return response()->json(['message' => 'Không tìm thấy khuyến mãi'], 404);

        if ($request->has('code')) {
            $request->validate([
                'discount_type' => 'required|integer|in:1,2',
                'discount_value' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $promotion->update([
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'max_discount_amount' => $request->max_discount_amount,
                'min_booking_value' => $request->min_booking_value ?? 0,
                'start_date' => date('Y-m-d H:i:s', strtotime($request->start_date)),
                'end_date' => date('Y-m-d H:i:s', strtotime($request->end_date)),
                'usage_limit' => $request->usage_limit,
                'usage_limit_per_user' =>  $request->usage_limit_per_user ?? 1,
                'status' => $request->status ?? 1,
            ]);
        } else if ($request->has('status')) {
            $promotion->update(['status' => $request->status]);
        }

        return response()->json(['message' => 'Cập nhật thành công', 'promotion' => $promotion], 200);
    }
}
