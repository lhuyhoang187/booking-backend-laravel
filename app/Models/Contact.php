<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'sender_type',
        'status',
        // BẮT BUỘC PHẢI THÊM 3 DÒNG NÀY ĐỂ LỄ TÂN CÓ THỂ LƯU PHẢN HỒI
        'reply_message',
        'replied_at',
        'replied_by'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
