<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    // Tắt timestamps vì bảng không có created_at, updated_at
    public $timestamps = false;

    protected $fillable = [
        'hotel_id', 'code', 'discount_type', 'discount_value', 'start_date', 'end_date'
    ];

    // Liên kết với bảng bookings để thống kê có bao nhiêu đơn đã dùng mã này
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'promotion_id');
    }
}