<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'price',
        'unit',
        'icon',
        'status'
    ];
    protected $guarded = [];
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
    public $timestamps = false;
}
