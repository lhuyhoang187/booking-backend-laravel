<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    // POST: /api/customer/bookings/{booking}/contacts
    public function storeBookingMessage(Request $request, int $bookingId)
    {
        $user = Auth::user();

        // 1. Kiểm tra đơn hàng có tồn tại và thuộc về user không
        $booking = Booking::where('id', $bookingId)->where('customer_id', $user->id)->first();
        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền!'], 403);
        }

        // 2. Validate dữ liệu khách nhập
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        // 3. LOGIC THÔNG MINH CHỐNG LỖI NULL:
        // Ưu tiên lấy name -> nếu ko có lấy full_name -> nếu ko có lấy guest_name của đơn đặt phòng -> cuối cùng là 'Khách hàng'
        $guestName = $user->name ?? $user->full_name ?? $booking->guest_name ?? 'Khách hàng';
        $guestEmail = $user->email ?? $booking->guest_email ?? 'no-reply@email.com';
        $guestPhone = $user->phone ?? $booking->guest_phone ?? null;

        // 4. Lưu vào Database (Bỏ customer_id để tránh lỗi thiếu cột ở Database)
        Contact::create([
            'name' => $guestName,
            'email' => $guestEmail,
            'phone' => $guestPhone,
            'subject' => $validated['subject'] ?? 'Hỗ trợ đơn hàng #' . $booking->booking_code,
            'message' => $validated['message'],
            'booking_id' => $booking->id,
            'status' => 0
        ]);

        return response()->json(['message' => 'Yêu cầu hỗ trợ đơn hàng đã được gửi tới lễ tân!'], 201);
    }
}
