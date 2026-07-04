<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Media; // Thêm dòng này cho chắc chắn (dù cùng thư mục)

class RoomType extends Model
{
    // Tắt timestamps vì bảng không có created_at, updated_at
    public $timestamps = false;

    protected $table = 'room_types';

    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'room_size',
        'bed_type', // Vẫn giữ lại cột cũ để hệ thống cũ không bị lỗi dữ liệu
        'base_price',
        'max_adults',
        'max_children',
        'status',
        'view_id',
        'bed_type_id',
        'description',
        'has_breakfast',
        'cancellation_policy',
        'smoking_policy'
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

    // 👇 THÊM MỚI: Liên kết với bảng Media để lấy hình ảnh phòng 👇
    public function media()
    {
        return $this->hasMany(Media::class, 'model_id')->where('model_type', 'RoomType');
    }

    public function roomView()
    {
        return $this->belongsTo(RoomView::class, 'view_id');
    }

    // 👇 ĐÃ THÊM: Mối quan hệ trỏ tới danh mục Loại Giường của Admin 👇
    public function bedTypeDetail()
    {
        return $this->belongsTo(BedType::class, 'bed_type_id');
    }

    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class, 'room_type_id');
    }
}