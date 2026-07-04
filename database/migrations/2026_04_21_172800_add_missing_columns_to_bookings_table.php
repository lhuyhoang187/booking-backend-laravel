<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Thêm các cột còn thiếu vào bảng bookings
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->string('guest_email')->nullable();
            $table->text('note')->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Xóa các cột này nếu có lệnh rollback
            $table->dropColumn(['check_in', 'check_out', 'guest_email', 'note', 'total_price']);
        });
    }
};