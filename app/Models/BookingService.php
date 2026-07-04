<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingService extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'booking_id',
        'service_id',
        'quantity',
        'price_at_booking',
        'note',
        'created_at'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
