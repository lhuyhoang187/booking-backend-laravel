<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Tắt tính năng tự động cập nhật timestamp vì bảng chỉ có created_at, không có updated_at
    public $timestamps = false; 

    // Các cột được phép thêm dữ liệu (dựa đúng 100% theo bảng users)
    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'phone',
        'is_active',
        'created_at'
    ];

    // Ẩn mật khẩu khi trả về API
    protected $hidden = [
        'password_hash',
    ];

    // Chỉ định cho Laravel biết cột mật khẩu của chúng ta tên là password_hash thay vì password
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}