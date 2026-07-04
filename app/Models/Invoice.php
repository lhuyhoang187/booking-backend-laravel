<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['booking_id', 'tax_code', 'company_name', 'company_address', 'receiving_email', 'status'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
