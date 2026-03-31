<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    // Bảng này chỉ có created_at, không có updated_at
    public $timestamps = false;

    protected $fillable = [
        'booking_code', 'user_id', 'hotel_id', 'promotion_id', 
        'guest_name', 'guest_phone', 'total_amount', 'platform_fee', 'status', 'created_at'
    ];

    // Liên kết với chi tiết đơn hàng (các phòng được đặt)
    public function details()
    {
        return $this->hasMany(BookingDetail::class, 'booking_id');
    }

    // Liên kết với thông tin thanh toán
    public function payment()
    {
        return $this->hasOne(Payment::class, 'booking_id');
    }
}