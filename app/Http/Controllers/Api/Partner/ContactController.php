<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    // Lấy tin nhắn gửi tới khách sạn của Partner đang đăng nhập
    public function index()
    {
        $hotelId = $this->getHotelId(); // Hàm này nằm trong Controller cha của bạn

        $contacts = Contact::whereHas('booking', function ($query) use ($hotelId) {
            $query->where('hotel_id', $hotelId);
        })->orWhereNull('booking_id')->get();

        return response()->json(['data' => $contacts]);
    }

    // Phản hồi tin nhắn
    public function reply(Request $request, int $id)
    {
        $contact = Contact::findOrFail($id);
        $request->validate(['reply_message' => 'required|string']);

        $contact->update([
            'reply_message' => $request->reply_message,
            'replied_at' => now(),
            'status' => 1 // Đánh dấu đã phản hồi
        ]);

        return response()->json(['message' => 'Đã gửi phản hồi thành công!']);
    }
}
