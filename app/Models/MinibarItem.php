<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinibarItem extends Model
{
    protected $fillable = ['hotel_id', 'name', 'price', 'stock_quantity', 'status'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
