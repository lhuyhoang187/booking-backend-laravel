<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;
    // Bảng hotels chỉ có created_at, không có updated_at
    public $timestamps = false;

    protected $fillable = [
        'partner_id',
        'name',
        'description',
        'address',
        'city',
        'star_rating',
        'status',
        'created_at',
        'tax_code',
        'business_license_url',
        'cancellation_policy'
    ];

    // Liên kết: Một Khách sạn có nhiều Loại phòng
    public function roomTypes()
    {
        return $this->hasMany(RoomType::class, 'hotel_id');
    }

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



    // Dán thêm 3 hàm này xuống cuối class Hotel
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function minibarItems()
    {
        return $this->hasMany(MinibarItem::class);
    }

    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }
    public function partner()
    {
        // Một khách sạn sẽ thuộc về một đối tác (dựa trên cột partner_id)
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
