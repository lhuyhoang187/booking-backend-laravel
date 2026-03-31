<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    // Use Case 1: Xem chi tiết hồ sơ
    public function getHotelProfile(Request $request)
    {
        // Lấy khách sạn của đối tác đang đăng nhập, kèm theo tiện ích và hình ảnh
        $hotel = Hotel::with(['amenities', 'images'])
            ->where('partner_id', $request->user()->id)
            ->first();

        if (!$hotel) {
            return response()->json(['message' => 'Bạn chưa tạo hồ sơ khách sạn.'], 404);
        }

        return response()->json([
            'message' => 'Lấy chi tiết hồ sơ thành công',
            'hotel' => $hotel
        ], 200);
    }

    // Use Case 2: Cập nhật thông tin chung (Thêm mới nếu chưa có)
    public function updateGeneralInfo(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'star_rating' => 'nullable|integer|min:1|max:5',
        ]);

        $user = $request->user();

        // Tìm khách sạn của user này, nếu chưa có thì tạo mới (updateOrCreate)
        $hotel = Hotel::updateOrCreate(
            ['partner_id' => $user->id], // Điều kiện tìm kiếm
            [ // Dữ liệu cần cập nhật hoặc thêm mới
                'name' => $request->name,
                'description' => $request->description,
                'address' => $request->address,
                'city' => $request->city,
                'star_rating' => $request->star_rating,
                'status' => 0, // Mặc định là 0 (Pending) chờ Admin duyệt theo tài liệu
                'created_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Cập nhật thông tin chung thành công!',
            'hotel' => $hotel
        ], 200);
    }

    // Use Case 3: Cập nhật tiện ích khách sạn
    public function updateAmenities(Request $request)
    {
        $request->validate([
            'amenity_ids' => 'required|array', // Yêu cầu gửi lên một mảng các ID tiện ích
            'amenity_ids.*' => 'integer|exists:amenities,id' // Từng ID phải tồn tại trong bảng amenities
        ]);

        $hotel = Hotel::where('partner_id', $request->user()->id)->first();

        if (!$hotel) {
            return response()->json(['message' => 'Vui lòng cập nhật thông tin chung trước.'], 400);
        }

        // Hàm sync() tự động xóa các tiện ích cũ và thêm các tiện ích mới vào bảng hotel_amenity
        $hotel->amenities()->sync($request->amenity_ids);

        return response()->json([
            'message' => 'Cập nhật tiện ích thành công!',
            'amenities' => $hotel->amenities
        ], 200);
    }

    // Use Case 4: Quản lý hình ảnh (Tải lên hình ảnh)
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Tối đa 2MB
            'is_primary' => 'boolean'
        ]);

        $hotel = Hotel::where('partner_id', $request->user()->id)->first();

        if (!$hotel) {
            return response()->json(['message' => 'Vui lòng cập nhật thông tin chung trước.'], 400);
        }

        // Lưu file vào thư mục public/storage/hotels
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('hotels', 'public');

            $media = Media::create([
                'model_type' => 'Hotel',
                'model_id' => $hotel->id,
                'file_url' => '/storage/' . $path,
                'is_primary' => $request->is_primary ?? 0
            ]);

            return response()->json([
                'message' => 'Tải hình ảnh thành công!',
                'media' => $media
            ], 201);
        }

        return response()->json(['message' => 'Có lỗi xảy ra khi tải ảnh.'], 500);
    }
}