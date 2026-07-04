<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSurcharge extends Model
{
    use HasFactory;

    protected $table = 'booking_surcharges';

    protected $fillable = [
        'booking_id',
        'surcharge_category_id',
        'amount',
        'note'
    ];

    // Mối quan hệ với Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    // MỐI QUAN HỆ MỚI: Lấy thông tin tên danh mục phụ thu
    public function category()
    {
        return $this->belongsTo(SurchargeCategory::class, 'surcharge_category_id');
    }
}
