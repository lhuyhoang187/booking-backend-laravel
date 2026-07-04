<?php

namespace App\Enums;

enum BookingStatus: int
{
    case Pending = 0;    // Chờ xử lý / Chưa thanh toán
    case Confirmed = 1;  // Đã xác nhận / Đã thanh toán cọc
    case CheckedIn = 2;  // Khách đang lưu trú
    case Completed = 3;  // Đã trả phòng / Hoàn thành
    case Cancelled = 4;  // Đã hủy
    case Refunded = 5;   // Đã hoàn tiền
}
