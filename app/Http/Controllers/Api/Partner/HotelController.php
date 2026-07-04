<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Media;
use App\Models\RoomType;
use App\Models\Booking;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HotelController extends Controller
{
    public function show(Request $request)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();

        if (!$hotelId) return response()->json(['message' => 'Bạn chưa tạo hồ sơ khách sạn.', 'hotel' => null], 200);

        $hotel = Hotel::with(['images'])->find($hotelId);
        return response()->json(['message' => 'Lấy chi tiết hồ sơ thành công', 'hotel' => $hotel], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'tax_code' => 'nullable|string|max:50', // 👉 THÊM MỚI
        ]);

        $hotelId = $this->getHotelId();
        $ownerId = $this->getOwnerId();

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'city' => $request->city,
            'star_rating' => $request->star_rating,
            'tax_code' => $request->tax_code, // 👉 THÊM MỚI
        ];

        if ($hotelId) {
            $hotel = Hotel::find($hotelId);
            $hotel->update($data);
        } else {
            $data['partner_id'] = $ownerId;
            $data['status'] = 0;
            $hotel = Hotel::create($data);
        }

        return response()->json(['message' => 'Cập nhật thông tin chung thành công!', 'hotel' => $hotel], 200);
    }

    public function uploadImage(Request $request)
    {
        // Nhận cả ảnh avatar (image) và ảnh giấy phép (business_license_image)
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
            'business_license_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480', // 👉 THÊM MỚI
            'is_primary' => 'boolean'
        ]);

        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);

        $hotel = Hotel::find($hotelId);

        // Upload ảnh đại diện (Lưu vào bảng media)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('hotels', 'public');
            Media::create([
                'model_type' => 'Hotel',
                'model_id' => $hotelId,
                'file_url' => '/storage/' . $path,
                'is_primary' => $request->is_primary ?? 0
            ]);
        }

        // 👉 THÊM MỚI: Upload ảnh Giấy phép kinh doanh (Lưu trực tiếp vào bảng hotels)
        if ($request->hasFile('business_license_image')) {
            $licensePath = $request->file('business_license_image')->store('hotels/licenses', 'public');
            $hotel->update(['business_license_url' => '/storage/' . $licensePath]);
        }

        return response()->json(['message' => 'Tải ảnh thành công!'], 201);
    }

    public function getStats(Request $request)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['pending_orders' => 0, 'total_room_types' => 0, 'total_revenue' => 0], 200);

        $totalRooms = RoomType::where('hotel_id', $hotelId)->count();
        $newBookings = Booking::where('hotel_id', $hotelId)->where('status', 0)->count();
        $revenue = Booking::where('hotel_id', $hotelId)->whereIn('status', [1, 2, 3])->sum('total_amount');

        return response()->json([
            'pending_orders' => $newBookings,
            'total_room_types' => $totalRooms,
            'total_revenue' => $revenue
        ], 200);
    }

    public function getHotelAmenities(Request $request)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();
        $allAmenities = Amenity::where('type', 1)->get();
        $selectedAmenityIds = [];

        if ($hotelId) {
            $selectedAmenityIds = DB::table('hotel_amenity')
                ->where('hotel_id', $hotelId)->pluck('amenity_id')->toArray();
        }
        return response()->json(['all_amenities' => $allAmenities, 'selected_ids' => $selectedAmenityIds], 200);
    }

    public function updateAmenities(Request $request)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Bạn chưa cập nhật thông tin khách sạn!'], 404);

        $request->validate(['amenity_ids' => 'array']);

        DB::table('hotel_amenity')->where('hotel_id', $hotelId)->delete();

        $insertData = [];
        if (!empty($request->amenity_ids)) {
            foreach ($request->amenity_ids as $a_id) {
                $insertData[] = ['hotel_id' => $hotelId, 'amenity_id' => $a_id];
            }
            DB::table('hotel_amenity')->insert($insertData);
        }
        return response()->json(['message' => 'Cập nhật tiện ích thành công!'], 200);
    }
}
