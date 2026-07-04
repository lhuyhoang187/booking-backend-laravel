<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurchargeCategory extends Model
{
    use HasFactory;

    protected $table = 'surcharge_categories';

    protected $fillable = [
        'hotel_id',
        'name',
        'description'
    ];
}
