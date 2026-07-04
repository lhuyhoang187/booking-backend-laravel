<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ChatThread;
use App\Models\ChatMessage;

class ChatController extends Controller
{

    // 👉 HÀM DÀNH CHO KHÁCH CHƯA ĐẶT PHÒNG (PRE-BOOKING)
    public function getPreBookingChat(int $hotelId)
    {
        $thread = ChatThread::firstOrCreate([
            'customer_id' => Auth::id(),
            'hotel_id' => $hotelId,
            'booking_id' => null
        ]);

        // 👉 Sửa: load kèm messages đã được sắp xếp
        return response()->json([
            'data' => $thread->load(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
        ]);
    }

    public function index(int $bookingId)
    {
        $booking = \App\Models\Booking::findOrFail($bookingId);
        $thread = ChatThread::firstOrCreate(
            ['booking_id' => $bookingId],
            ['customer_id' => Auth::id(), 'hotel_id' => $booking->hotel_id]
        );

        // 👉 Sửa: load kèm messages đã được sắp xếp
        return response()->json([
            'data' => $thread->load(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
        ]);
    }

    public function store(Request $request, int $threadId)
    {
        $request->validate(['message' => 'required|string']);

        $msg = ChatMessage::create([
            'thread_id' => $threadId,
            'sender_id' => Auth::id(), // Dùng Auth::id() để hết lỗi
            'sender_type' => 'customer',
            'message' => $request->message
        ]);
        return response()->json($msg, 201);
    }

    // 👉 HÀM MỚI: Lấy tất cả lịch sử trò chuyện của khách hàng
    public function getAllThreads()
    {
        $threads = ChatThread::where('customer_id', Auth::id())
            ->with(['hotel' => function ($q) {
                $q->select('id', 'name'); // Chỉ lấy id và tên khách sạn cho nhẹ
            }, 'booking' => function ($q) {
                $q->select('id', 'booking_code');
            }, 'latestMessage']) // Load tin nhắn mới nhất
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['data' => $threads]);
    }
}
