<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoomAssignment extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
