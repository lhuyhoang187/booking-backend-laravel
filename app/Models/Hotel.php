<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    // Bảng hotels chỉ có created_at, không có updated_at
    public $timestamps = false; 

    protected $fillable = [
        'partner_id', 'name', 'description', 'address', 'city', 'star_rating', 'status', 'created_at'
    ];

    // Liên kết nhiều-nhiều với bảng amenities qua bảng trung gian hotel_amenity
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'hotel_amenity', 'hotel_id', 'amenity_id');
    }

    // Liên kết với bảng media để lấy hình ảnh (điều kiện model_type = 'Hotel')
    public function images()
    {
        return $this->hasMany(Media::class, 'model_id')->where('model_type', 'Hotel');
    }
}