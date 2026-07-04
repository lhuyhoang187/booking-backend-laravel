<?php

namespace App\Http\Controllers\Api\PublicArea;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

// Đã đổi tên class khớp với tên file SystemContactController.php
class SystemContactController extends Controller
{
    // POST: /api/contacts
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        // Kiểm tra xem người gửi có đang đăng nhập không (lấy ID để Admin tiện hỗ trợ)
        $customerId = auth('sanctum')->check() ? auth('sanctum')->id() : null;

        Contact::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? 'Liên hệ từ trang chủ',
            'message' => $validated['message'],
            'customer_id' => $customerId, // Lưu lại ID nếu có, không có thì null
            'status' => 0 // 0: Mới gửi, chưa xử lý
        ]);

        return response()->json(['message' => 'Tin nhắn của bạn đã được gửi đến Ban Quản Trị thành công!'], 201);
    }
}
