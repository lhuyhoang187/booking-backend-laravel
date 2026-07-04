<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    // Bảng promotions CÓ created_at và updated_at nên KHÔNG tắt timestamps
    // public $timestamps = false; (Đã xóa)

    protected $fillable = [
        'hotel_id',
        'code',
        'discount_type',
        'discount_value',
        'max_discount_amount', // Đã bổ sung trường Giảm tối đa
        'start_date',
        'end_date',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'min_booking_value',
        'status'
    ];

    // Liên kết với bảng bookings để thống kê có bao nhiêu đơn đã dùng mã này
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'promotion_id');
    }
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
