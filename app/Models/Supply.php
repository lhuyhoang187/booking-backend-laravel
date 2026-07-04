<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'hotel_id',
        'name',
        'price_per_unit',
        'status'
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function incidents()
    {
        return $this->hasMany(SupplyIncident::class);
    }
}
