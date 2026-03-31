<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'icon', 'type'];
}