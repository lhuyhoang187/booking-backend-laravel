<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingMinibar extends Model
{
    protected $fillable = [
        'booking_id',
        'minibar_item_id',
        'quantity',
        'price_at_checkout'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function minibarItem()
    {
        return $this->belongsTo(MinibarItem::class);
    }
}
