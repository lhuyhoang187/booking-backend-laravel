<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// 1. Nhóm Public (Khách vãng lai)
use App\Http\Controllers\Api\PublicArea\AuthController as PublicAuthController;
use App\Http\Controllers\Api\PublicArea\HotelController as PublicHotelController;
use App\Http\Controllers\Api\PublicArea\RoomTypeController as PublicRoomTypeController;
use App\Http\Controllers\Api\PublicArea\ReviewController as PublicReviewController;

// 2. Nhóm Customer (Khách hàng)
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Api\Customer\FavoriteController as CustomerFavoriteController;
use App\Http\Controllers\Api\Customer\ReviewController as CustomerReviewController;
use App\Http\Controllers\Api\Customer\PromotionController as CustomerPromotionController;


// 3. Nhóm Partner (Đối tác khách sạn)
use App\Http\Controllers\Api\Partner\AuthController as PartnerAuthController;
use App\Http\Controllers\Api\Partner\ProfileController as PartnerProfileController; // 👉 Đã thêm mới
use App\Http\Controllers\Api\Partner\StaffController as PartnerStaffController;     // 👉 Đã thêm mới
use App\Http\Controllers\Api\Partner\HotelController as PartnerHotelController;
use App\Http\Controllers\Api\Partner\RoomTypeController as PartnerRoomTypeController;
use App\Http\Controllers\Api\Partner\RoomController as PartnerRoomController;
use App\Http\Controllers\Api\Partner\ServiceController as PartnerServiceController;
use App\Http\Controllers\Api\Partner\PromotionController as PartnerPromotionController;
use App\Http\Controllers\Api\Partner\BookingController as PartnerBookingController;
use App\Http\Controllers\Api\Partner\SurchargeCategoryController;
use App\Http\Controllers\Api\Partner\SupplyController;
use App\Http\Controllers\Api\Customer\ChatController as CustomerChatController;
use App\Http\Controllers\Api\PublicArea\SystemContactController as PublicSystemContactController;
use App\Http\Controllers\Api\Partner\ChatController as PartnerChatController;
use App\Http\Controllers\Api\Partner\RoomInventoryController as PartnerRoomInventoryController;
// 4. Nhóm Admin (Quản trị viên)
use App\Http\Controllers\Api\Admin\PartnerApprovalController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\AmenityController;
use App\Http\Controllers\Api\Admin\RoomViewController;
use App\Http\Controllers\Api\Admin\BedTypeController;
use App\Http\Controllers\Api\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Api\Admin\PromotionController as AdminPromotionController;

// ==========================================
// 1. NHÓM API CÔNG KHAI (KHÔNG YÊU CẦU ĐĂNG NHẬP)
// ==========================================

// -- Xác thực (Auth) --
Route::post('/login', [PublicAuthController::class, 'login']);
Route::post('/auth/register', [PublicAuthController::class, 'register']);
Route::post('/partner/register', [PublicAuthController::class, 'registerPartner']);

// -- Dữ liệu Master & Bộ lọc --
Route::get('/hotels/filters-data', [PublicHotelController::class, 'getFiltersData']);
Route::get('/rooms/master-data', [PublicHotelController::class, 'getRoomMasterData']);

// -- Tìm kiếm & Xem chi tiết --
Route::get('/hotels/search', [PublicHotelController::class, 'search']);
Route::get('/hotels/{id}', [PublicHotelController::class, 'getDetail']);
Route::get('/rooms/{id}', [PublicRoomTypeController::class, 'show']);
Route::get('/hotels/{hotel_id}/reviews', [PublicReviewController::class, 'index']);
Route::get('/hotels/{hotel_id}/services', [CustomerBookingController::class, 'getHotelServices']);

Route::post('/contacts', [PublicSystemContactController::class, 'store']);
Route::get('/promotions/active', [CustomerPromotionController::class, 'getActivePromotions']);
// -- Route lấy ảnh --
Route::get('/get-image', function (Request $request) {
    $relativePath = str_replace('/storage/', '', $request->query('path'));
    $fullPath = storage_path('app/public/' . $relativePath);
    if (!file_exists($fullPath)) {
        return response()->json(['message' => 'Không tìm thấy ảnh'], 404);
    }
    return response()->file($fullPath);
});


// ==========================================
// THÊM MỚI: API ĐĂNG XUẤT CHUNG CHO MỌI ROLE
// ==========================================
Route::middleware('auth:sanctum')->post('/logout', [PublicAuthController::class, 'logout']);


// ==========================================
// 2. NHÓM API KHÁCH HÀNG (YÊU CẦU ĐĂNG NHẬP CUSTOMER)
// ==========================================
Route::middleware('auth:sanctum')->prefix('customer')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // -- Quản lý Hồ sơ (Profile) --
    Route::get('/profile', [CustomerAuthController::class, 'getProfile']);
    Route::put('/profile', [CustomerAuthController::class, 'updateProfile']);
    Route::post('/change-password', [CustomerAuthController::class, 'changePassword']);
    Route::delete('/account', [CustomerAuthController::class, 'deleteAccount']);

    // -- Quản lý Đặt phòng (Bookings) --
    Route::post('/bookings', [CustomerBookingController::class, 'createBooking']);
    Route::get('/my-bookings', [CustomerBookingController::class, 'myBookings']);
    Route::post('/bookings/{id}/cancel', [CustomerBookingController::class, 'cancelMyBooking']);

    // -- Quản lý Yêu thích & Đánh giá --
    Route::get('/favorites', [CustomerFavoriteController::class, 'getFavorites']);
    Route::post('/favorites/{hotelId}', [CustomerFavoriteController::class, 'toggleFavorite']);
    Route::post('/reviews', [CustomerReviewController::class, 'store']);

    Route::get('/bookings/{booking}/chat', [CustomerChatController::class, 'index']);
    Route::get('/hotels/{hotelId}/chat', [CustomerChatController::class, 'getPreBookingChat']);
    Route::get('/chats', [CustomerChatController::class, 'getAllThreads']);
    Route::post('/chat/{thread}/messages', [CustomerChatController::class, 'store']);
    Route::get('/chat/{threadId}/messages', [PartnerChatController::class, 'getMessages']);

    Route::post('/promotions/check', [CustomerPromotionController::class, 'checkPromotion']);
});


// ==========================================
// 3. NHÓM API ĐỐI TÁC KHÁCH SẠN (YÊU CẦU ĐĂNG NHẬP PARTNER)
// ==========================================
Route::middleware('auth:sanctum')->prefix('partner')->group(function () {

    // -- Quản lý Hồ sơ Chủ khách sạn --
    Route::get('/profile', [PartnerProfileController::class, 'getProfile']);
    Route::put('/profile', [PartnerProfileController::class, 'updateProfile']);
    Route::put('/profile/change-password', [PartnerProfileController::class, 'changePassword']);

    // -- Quản lý Thông tin Khách sạn --
    Route::get('/hotel', [PartnerHotelController::class, 'show']);
    Route::put('/hotel', [PartnerHotelController::class, 'update']);
    Route::post('/hotel/images', [PartnerHotelController::class, 'uploadImage']);
    Route::get('/dashboard-stats', [PartnerHotelController::class, 'getStats']);
    Route::get('/hotel/amenities', [PartnerHotelController::class, 'getHotelAmenities']);
    Route::post('/hotel/amenities', [PartnerHotelController::class, 'updateAmenities']);

    // -- Quản lý Loại Phòng (Hạng phòng) --
    Route::apiResource('room-types', PartnerRoomTypeController::class)->except(['show']);
    Route::post('/room-types/{id}/amenities', [PartnerRoomTypeController::class, 'updateAmenities']);
    Route::post('/room-types/{id}/media', [PartnerRoomTypeController::class, 'uploadMedia']);

    // -- Quản lý Sơ đồ Phòng vật lý --
    // (Vì bạn dùng tên hàm tự chế là getRooms, storeRoom... nên bắt buộc phải khai báo rời từng dòng như thế này)
    Route::get('/room-amenities', [PartnerRoomController::class, 'getRoomAmenities']);
    Route::get('/rooms', [PartnerRoomController::class, 'getRooms']);
    Route::post('/rooms', [PartnerRoomController::class, 'storeRoom']);
    Route::put('/rooms/{id}', [PartnerRoomController::class, 'updateRoom']);
    Route::delete('/rooms/{id}', [PartnerRoomController::class, 'deleteRoom']);
    Route::get('/rooms/available/{roomTypeId}', [PartnerRoomController::class, 'getAvailableRoomsByType']);

    // -- Quản lý Dịch vụ --
    Route::get('/services', [PartnerServiceController::class, 'getServices']);
    Route::post('/services', [PartnerServiceController::class, 'storeService']);
    Route::put('/services/{id}', [PartnerServiceController::class, 'updateService']);
    Route::delete('/services/{id}', [PartnerServiceController::class, 'deleteService']);
    Route::get('/surcharge-categories', [PartnerServiceController::class, 'getSurchargeCategories']);
    Route::post('/bookings/{id}/surcharges', [PartnerServiceController::class, 'addBookingSurcharge']);

    // -- Quản lý Minibar --
    Route::get('/minibars', [PartnerServiceController::class, 'getMinibars']);
    Route::post('/minibars', [PartnerServiceController::class, 'storeMinibar']);
    Route::put('/minibars/{id}', [PartnerServiceController::class, 'updateMinibar']);
    Route::delete('/minibars/{id}', [PartnerServiceController::class, 'deleteMinibar']);

    // -- Quản lý Đồ dùng tiêu hao --
    Route::get('/supplies', [SupplyController::class, 'getSupplies']);
    Route::post('/supplies', [SupplyController::class, 'storeSupply']);
    Route::put('/supplies/{id}', [SupplyController::class, 'updateSupply']);
    Route::delete('/supplies/{id}', [SupplyController::class, 'deleteSupply']);

    // -- Quản lý Khuyến mãi --
    Route::patch('/promotions/{id}/end-early', [PartnerPromotionController::class, 'endEarly']);
    Route::get('/promotions/{id}/stats', [PartnerPromotionController::class, 'stats']);
    Route::apiResource('promotions', PartnerPromotionController::class)->only(['index', 'store', 'update']);

    // -- Quản lý Đặt phòng (Check-in/Check-out/Menu) --
    Route::get('/bookings', [PartnerBookingController::class, 'index']);
    Route::get('/bookings/{id}', [PartnerBookingController::class, 'show']);
    Route::get('/bookings/{id}/payment', [PartnerBookingController::class, 'getPaymentInfo']);
    Route::get('/bookings/{id}/available-rooms', [PartnerBookingController::class, 'getAvailableRooms']);
    Route::put('/bookings/{id}/guests', [PartnerBookingController::class, 'updateGuests']);
    Route::put('/bookings/{id}/change-room', [PartnerBookingController::class, 'changeRoom']);

    Route::get('/bookings/{id}/menu', [PartnerBookingController::class, 'getMenuAndCart']);
    Route::post('/bookings/{id}/add-service', [PartnerBookingController::class, 'addExtraService']);
    Route::post('/bookings/{id}/add-minibar', [PartnerBookingController::class, 'addExtraMinibar']);
    Route::delete('/bookings/{id}/remove-service/{cartId}', [PartnerBookingController::class, 'removeExtraService']);
    Route::put('/bookings/{id}/update-service/{cartId}', [PartnerBookingController::class, 'updateExtraService']);
    Route::put('/bookings/{id}/notes', [PartnerBookingController::class, 'updateBookingNotes']);

    Route::put('/bookings/{id}/confirm', [PartnerBookingController::class, 'confirmBooking']);
    Route::put('/bookings/{id}/check-out', [PartnerBookingController::class, 'checkOutAndPay']);
    Route::put('/bookings/{id}/cancel', [PartnerBookingController::class, 'cancelBooking']);
    Route::put('/bookings/{id}/estimated-time', [PartnerBookingController::class, 'updateEstimatedTime']);
    Route::put('/bookings/{id}/no-show', [PartnerBookingController::class, 'markAsNoShow']);
    Route::post('/bookings/{id}/check-in', [PartnerBookingController::class, 'checkIn']);

    // -- Quản lý Phụ thu & Đền bù --
    Route::apiResource('surcharge-categories', SurchargeCategoryController::class);
    Route::post('/bookings/{id}/add-surcharge', [PartnerBookingController::class, 'addSurcharge']);
    Route::delete('/bookings/{id}/remove-surcharge/{surchargeId}', [PartnerBookingController::class, 'removeSurcharge']);
    Route::post('/bookings/{id}/add-damaged-item', [PartnerBookingController::class, 'addDamagedItem']);
    Route::delete('/bookings/{id}/remove-damaged-item/{itemId}', [PartnerBookingController::class, 'removeDamagedItem']);

    // Lấy danh sách tất cả hội thoại của khách sạn
    Route::get('/chat/threads', [PartnerChatController::class, 'index']);
    // Nhắn tin phản hồi
    Route::post('/chat/{thread}/messages', [PartnerChatController::class, 'store']);
    Route::put('/chat/threads/{id}/status', [PartnerChatController::class, 'updateStatus']);

    // -- Quản lý Nhân viên --
    Route::apiResource('staffs', PartnerStaffController::class)->except(['show']);
    Route::apiResource('roles', App\Http\Controllers\Api\Partner\PartnerRoleController::class);
    Route::get('roles', [App\Http\Controllers\Api\Partner\StaffController::class, 'getRoles']);

    Route::get('/room-inventory', [PartnerRoomInventoryController::class, 'index']);
    Route::post('/room-inventory/bulk-update', [PartnerRoomInventoryController::class, 'updateBulk']);
    Route::get('/room-matrix-grid', [App\Http\Controllers\Api\Partner\RoomMatrixController::class, 'getMatrix']);
});


// ==========================================
// 4. NHÓM API ADMIN (YÊU CẦU ĐĂNG NHẬP ADMIN)
// ==========================================
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // -- Quản lý xét duyệt Đối tác/Khách sạn --
    Route::get('/pending-partners', [PartnerApprovalController::class, 'getPendingPartners']);
    Route::post('/approve-partner/{hotelId}', [PartnerApprovalController::class, 'approvePartner']);
    Route::post('/reject-partner/{hotelId}', [PartnerApprovalController::class, 'rejectPartner']);
    Route::get('/approved-partners', [PartnerApprovalController::class, 'getApprovedPartners']);
    Route::post('/suspend-partner/{id}', [PartnerApprovalController::class, 'suspendPartner']);

    // -- Quản lý Khách hàng --
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers/{id}/toggle-status', [CustomerController::class, 'toggleStatus']);

    Route::apiResource('amenities', AmenityController::class);
    Route::apiResource('room-views', RoomViewController::class);
    Route::apiResource('bed-types', BedTypeController::class);


    // 👉 THÊM 2 DÒNG NÀY ĐỂ XỬ LÝ LIÊN HỆ CỦA KHÁCH:
    Route::get('/contacts', [AdminContactController::class, 'index']);
    Route::put('/contacts/{id}/resolve', [AdminContactController::class, 'resolve']);

    Route::get('/promotions', [AdminPromotionController::class, 'index']);
    Route::post('/promotions', [AdminPromotionController::class, 'store']);
    Route::put('/promotions/{id}', [AdminPromotionController::class, 'update']);
});
