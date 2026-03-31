<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoomType extends Model
{
    // Tắt timestamps vì bảng không có created_at, updated_at
    public $timestamps = false;

    protected $fillable = [
        'hotel_id', 'name', 'slug', 'base_price', 'max_adults', 'max_children'
    ];

    // Tự động tạo slug (đường dẫn chuẩn SEO) từ tên loại phòng khi lưu
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($roomType) {
            if (empty($roomType->slug)) {
                $roomType->slug = Str::slug($roomType->name) . '-' . time();
            }
        });
    }

    // Liên kết với bảng tiện ích
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'room_type_amenity', 'room_type_id', 'amenity_id');
    }

    // Liên kết ngược lại với khách sạn
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}