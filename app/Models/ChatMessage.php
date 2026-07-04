<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    // Bảng này không có cột updated_at, nên cần khai báo để Laravel không tìm nó
    public $timestamps = false;

    // Khai báo các cột được phép gán dữ liệu hàng loạt
    protected $fillable = [
        'thread_id',
        'sender_id',
        'sender_type',
        'message',
        'created_at'
    ];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }
}
