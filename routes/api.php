<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HotelController; // <-- Thêm dòng này
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PromotionController;

// Nhóm 1: API dành cho Đối tác khách sạn (Không cần đăng nhập)
Route::prefix('partner')->group(function () {
    Route::post('/register', [AuthController::class, 'registerPartner']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Nhóm 2: API dành cho Đối tác khách sạn (BẮT BUỘC có Bearer Token)
Route::prefix('partner')->middleware('auth:sanctum')->group(function () {
    // API Quản lý tài khoản
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    // API Quản lý hồ sơ khách sạn (THÊM MỚI Ở ĐÂY)
    Route::get('/hotel', [HotelController::class, 'getHotelProfile']);
    Route::post('/hotel/info', [HotelController::class, 'updateGeneralInfo']);
    Route::post('/hotel/amenities', [HotelController::class, 'updateAmenities']);
    Route::post('/hotel/images', [HotelController::class, 'uploadImage']);
    // ... (Các route cũ của hotel để nguyên) ...

    // API Quản lý Loại phòng (Thêm mới ở đây)
    Route::get('/room-types', [RoomTypeController::class, 'index']);
    Route::post('/room-types', [RoomTypeController::class, 'store']);
    Route::put('/room-types/{id}', [RoomTypeController::class, 'update']);
    Route::delete('/room-types/{id}', [RoomTypeController::class, 'destroy']);
    Route::post('/room-types/{id}/amenities', [RoomTypeController::class, 'updateAmenities']);
    // ... các route cũ của profile, hotel, room-types ...

    // API Quản lý Đơn đặt hàng
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::get('/bookings/{id}/payment', [BookingController::class, 'getPaymentInfo']);
    Route::put('/bookings/{id}/confirm', [BookingController::class, 'confirmBooking']);
    Route::put('/bookings/{id}/check-in', [BookingController::class, 'checkInBooking']);
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);
    // ... các route cũ của profile, hotel, room-types, bookings ...

    // API Quản lý Khuyến mãi
    Route::get('/promotions', [PromotionController::class, 'index']);
    Route::post('/promotions', [PromotionController::class, 'store']);
    Route::put('/promotions/{id}', [PromotionController::class, 'update']);
    Route::put('/promotions/{id}/end-early', [PromotionController::class, 'endEarly']);
    Route::get('/promotions/{id}/stats', [PromotionController::class, 'stats']);
});