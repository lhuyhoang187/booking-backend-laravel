<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Room;

// Lệnh mẫu của Laravel
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 👉 LỆNH TỰ ĐỘNG QUÉT ĐƠN HÀNG MỖI NGÀY
// 1. Quét đơn Hủy (Chạy 1h sáng - dọn dẹp đơn cũ)
Schedule::call(function () {
    Booking::where('status', 0)
        ->where('created_at', '<', now()->subDay())
        ->update(['status' => 4]);
})->dailyAt('01:00');

// 2. Quét đơn NO-SHOW (Chạy 22h tối - để kịp bán phòng đêm)
Schedule::call(function () {
    $noShowBookings = Booking::where('status', 1)
        ->where('check_in', '<', now()->toDateString())
        ->get();

    foreach ($noShowBookings as $booking) {
        DB::transaction(function () use ($booking) {
            $roomIds = $booking->roomAssignments->pluck('room_id');
            Room::whereIn('id', $roomIds)->update(['status' => 1]);
            $booking->update(['status' => 5]);
        });
    }
})->dailyAt('22:00');
