<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $fillable = [
        'booking_id',
        'hotel_id',
        'customer_id',
        'rating',
        'comment',
        'partner_reply',
        'replied_at',
        'status'
    ];

    // Mối quan hệ 1 Đánh giá có Nhiều Ảnh
    public function images()
    {
        return $this->hasMany(ReviewImage::class, 'review_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}
