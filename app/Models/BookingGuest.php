<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuest extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
