<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Media;
use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    // Use Case 1: Xem danh sách loại phòng
    public function index(Request $request)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomTypes = RoomType::with(['amenities', 'media'])
            ->where('hotel_id', $hotelId)
            ->where('status', 1)
            ->get();

        $allRoomAmenities = Amenity::where('type', 2)->get();

        $allRoomViews = \App\Models\RoomView::where('status', 1)->get();
        $allBedTypes = \App\Models\BedType::where('status', 1)->get();

        return response()->json([
            'message' => 'Lấy danh sách loại phòng thành công',
            'room_types' => $roomTypes,
            'all_room_amenities' => $allRoomAmenities,
            'all_room_views' => $allRoomViews,
            'all_bed_types' => $allBedTypes
        ], 200);
    }

    // Use Case 2: Thêm loại phòng
    public function store(Request $request)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        // 👉 ĐÃ THÊM: Validate các trường mới
        $request->validate([
            'name' => 'required|string|max:100',
            'room_size' => 'nullable|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'required|integer|min:0',
            'view_id' => 'nullable|integer|exists:room_views,id',
            'bed_type_id' => 'nullable|integer|exists:bed_types,id',
            'status' => 'nullable|integer|in:0,1',
            'description' => 'nullable|string',
            'has_breakfast' => 'nullable|in:0,1',
            'cancellation_policy' => 'nullable|string|max:255',
            'smoking_policy' => 'nullable|in:0,1'
        ]);

        // 👉 ĐÃ THÊM: Lưu các trường mới vào DB
        $roomType = RoomType::create([
            'hotel_id' => $hotelId,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . time(),
            'room_size' => $request->room_size,
            'view_id' => $request->view_id,
            'bed_type_id' => $request->bed_type_id,
            'base_price' => $request->base_price,
            'max_adults' => $request->max_adults,
            'max_children' => $request->max_children,
            'status' => $request->status ?? 1,
            'description' => $request->description,
            'has_breakfast' => $request->has_breakfast ?? 0,
            'cancellation_policy' => $request->cancellation_policy,
            'smoking_policy' => $request->smoking_policy ?? 0,
        ]);

        $amenityIds = $request->input('amenity_ids', []);
        if (!empty($amenityIds)) {
            $roomType->amenities()->sync($amenityIds);
        }

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $index => $file) {
                $path = $file->store('room_media', 'public');
                Media::create([
                    'model_type' => 'RoomType',
                    'model_id'   => $roomType->id,
                    'file_url'   => '/storage/' . $path,
                    'is_primary' => ($index === 0) ? 1 : 0,
                    'sort_order' => $index
                ]);
            }
        }

        return response()->json([
            'message' => 'Thêm loại phòng thành công',
            'room_type' => $roomType
        ], 201);
    }

    // Use Case 3: Cập nhật thông tin loại phòng
    public function update(Request $request, int $id)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomType = RoomType::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$roomType) return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);

        // 👉 ĐÃ THÊM: Validate các trường mới
        $request->validate([
            'name' => 'required|string|max:100',
            'room_size' => 'nullable|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'required|integer|min:0',
            'status' => 'nullable|integer|in:0,1',
            'view_id' => 'nullable|integer',
            'bed_type_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'has_breakfast' => 'nullable|in:0,1',
            'cancellation_policy' => 'nullable|string|max:255',
            'smoking_policy' => 'nullable|in:0,1'
        ]);

        // 👉 ĐÃ THÊM: Cập nhật các trường mới vào DB
        $roomType->update([
            'name' => $request->name,
            'room_size' => $request->room_size,
            'base_price' => $request->base_price,
            'max_adults' => $request->max_adults,
            'max_children' => $request->max_children,
            'status' => $request->status ?? 1,
            'view_id' => $request->view_id,
            'bed_type_id' => $request->bed_type_id,
            'description' => $request->description,
            'has_breakfast' => $request->has_breakfast ?? 0,
            'cancellation_policy' => $request->cancellation_policy,
            'smoking_policy' => $request->smoking_policy ?? 0,
        ]);

        $amenityIds = $request->input('amenity_ids', []);
        $roomType->amenities()->sync($amenityIds);

        if ($request->hasFile('media')) {
            Media::where('model_type', 'RoomType')->where('model_id', $roomType->id)->delete();
            foreach ($request->file('media') as $index => $file) {
                $path = $file->store('room_media', 'public');
                Media::create([
                    'model_type' => 'RoomType',
                    'model_id'   => $roomType->id,
                    'file_url'   => '/storage/' . $path,
                    'is_primary' => ($index === 0) ? 1 : 0,
                    'sort_order' => $index
                ]);
            }
        }

        return response()->json([
            'message' => 'Cập nhật loại phòng thành công',
            'room_type' => $roomType
        ], 200);
    }

    // Use Case 4: Cập nhật tiện ích cho loại phòng 
    public function updateAmenities(Request $request, int $id)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomType = RoomType::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$roomType) return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);

        $request->validate([
            'amenity_ids' => 'required|array',
            'amenity_ids.*' => 'integer|exists:amenities,id'
        ]);

        $roomType->amenities()->sync($request->amenity_ids);

        return response()->json([
            'message' => 'Cập nhật tiện ích phòng thành công',
            'amenities' => $roomType->amenities
        ], 200);
    }

    // Use Case 5: Xóa/Vô hiệu hóa loại phòng
    public function destroy(Request $request, int $id)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomType = RoomType::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$roomType) return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);

        Room::where('room_type_id', $roomType->id)
            ->where('hotel_id', $hotelId)
            ->update(['status' => 0]);

        $roomType->status = 0;
        $roomType->save();

        return response()->json(['message' => 'Đã vô hiệu hóa loại phòng và các phòng vật lý liên quan thành công!'], 200);
    }

    // Use Case 6: Tải Ảnh / Video cho loại phòng
    public function uploadMedia(Request $request, int $id)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $roomType = RoomType::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$roomType) return response()->json(['message' => 'Không tìm thấy loại phòng'], 404);

        $request->validate([
            'media' => 'required|array',
            'media.*' => 'file|mimes:jpeg,png,jpg,webp,mp4,mov,avi|max:20480',
        ]);

        $uploadedPaths = [];

        if ($request->hasFile('media')) {
            Media::where('model_type', 'RoomType')->where('model_id', $roomType->id)->delete();

            foreach ($request->file('media') as $index => $file) {
                $path = $file->store('room_media', 'public');

                $media = Media::create([
                    'model_type' => 'RoomType',
                    'model_id'   => $roomType->id,
                    'file_url'   => '/storage/' . $path,
                    'is_primary' => ($index === 0) ? 1 : 0,
                    'sort_order' => $index
                ]);

                $uploadedPaths[] = asset('storage/' . $path);
            }
        }

        return response()->json([
            'message' => 'Tải media lên và lưu Database thành công!',
            'paths' => $uploadedPaths
        ], 200);
    }
}
