<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // 1. Lấy danh sách tất cả các liên hệ gửi cho hệ thống
    public function index()
    {
        // Lấy danh sách, sắp xếp tin nhắn mới nhất lên đầu
        $contacts = Contact::orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $contacts]);
    }

    // 2. Đánh dấu đã giải quyết (chuyển status thành 1)
    public function resolve(int $id)
    {
        $contact = Contact::findOrFail($id);

        $contact->update([
            'status' => 1
        ]);

        return response()->json(['message' => 'Đã đánh dấu xử lý thành công!']);
    }
}
