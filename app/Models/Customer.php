<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // Tắt timestamps nếu bảng customers của bạn không có created_at, updated_at
    // public $timestamps = false; 

    // Thay thế $guarded bằng $fillable để bảo mật chặt chẽ hơn
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password_hash',
        'status',
        // Thêm các cột khác trong bảng customers của bạn vào đây...
    ];

    // Ẩn mật khẩu khi API trả về cục data JSON
    protected $hidden = [
        'password_hash',
    ];

    // Chỉ đường cho Laravel biết cột mật khẩu tên là gì
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // ==========================================
    // CÁC LIÊN KẾT (RELATIONSHIPS)
    // ==========================================

    // Lấy ra các khách sạn mà khách hàng này yêu thích
    public function favoriteHotels()
    {
        return $this->belongsToMany(Hotel::class, 'favorites', 'customer_id', 'hotel_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }
}
