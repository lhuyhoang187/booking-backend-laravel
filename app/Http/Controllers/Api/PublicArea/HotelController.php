<?php

namespace App\Http\Controllers\Api\PublicArea;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Amenity;
use App\Models\Room;
use App\Models\BookingDetail;
use App\Models\RoomView;
use App\Models\BedType;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function search(Request $request)
    {
        $destination = $request->query('destination');
        $checkIn = $request->query('check_in') ?: $request->query('checkIn');
        $checkOut = $request->query('check_out') ?: $request->query('checkOut');

        // 👉 ĐÃ THÊM: Lấy số lượng phòng khách yêu cầu (Mặc định là 1)
        $requestedRooms = $request->query('rooms', 1);

        $stars = $request->query('stars');
        $hotelAmenities = $request->query('hotel_amenities');
        $roomAmenities = $request->query('room_amenities');
        $priceMax = $request->query('price_max');

        if ($stars && !is_array($stars)) $stars = explode(',', $stars);
        if ($hotelAmenities && !is_array($hotelAmenities)) $hotelAmenities = explode(',', $hotelAmenities);
        if ($roomAmenities && !is_array($roomAmenities)) $roomAmenities = explode(',', $roomAmenities);

        $query = Hotel::with(['images', 'roomTypes.amenities', 'amenities'])->where('status', 1);

        if ($destination && $destination != '') {
            $query->where('city', 'LIKE', '%' . $destination . '%');
        }

        if (!empty($stars)) {
            $query->whereIn('star_rating', $stars);
        }

        if (!empty($hotelAmenities)) {
            $query->whereHas('amenities', function ($q) use ($hotelAmenities) {
                $q->whereIn('amenities.id', $hotelAmenities);
            });
        }

        if (!empty($roomAmenities)) {
            $query->whereHas('roomTypes.amenities', function ($q) use ($roomAmenities) {
                $q->whereIn('amenities.id', $roomAmenities);
            });
        }

        $hotels = $query->get();
        $filteredHotels = collect();

        foreach ($hotels as $hotel) {
            $hasAvailableRoom = false;
            $minPrice = PHP_INT_MAX;

            if ($hotel->roomTypes->count() > 0) {
                foreach ($hotel->roomTypes as $roomType) {

                    // 👉 TÍNH SỐ PHÒNG TRỐNG NGAY TRONG LÚC TÌM KIẾM
                    if ($checkIn && $checkOut) {
                        $totalPhysicalRooms = Room::where('hotel_id', $hotel->id)
                            ->where('room_type_id', $roomType->id)
                            ->where('status', '!=', 0)
                            ->count();

                        $bookedRoomsCount = BookingDetail::where('room_type_id', $roomType->id)
                            ->whereHas('booking', function ($query) use ($checkIn, $checkOut) {
                                $query->whereIn('status', [1, 2])
                                    ->where('check_in', '<', $checkOut)
                                    ->where('check_out', '>', $checkIn);
                            })->sum('rooms_count');

                        $availableRooms = max(0, $totalPhysicalRooms - $bookedRoomsCount);

                        // Nếu số phòng trống < số phòng yêu cầu -> Bỏ qua hạng phòng này
                        if ($availableRooms < $requestedRooms) continue;
                    }

                    // Nếu lọt xuống được đây, nghĩa là Hạng phòng này ĐỦ phòng cho khách!
                    $hasAvailableRoom = true;
                    if ($roomType->base_price < $minPrice) {
                        $minPrice = $roomType->base_price;
                    }
                }

                // Nếu khách sạn không có bất kỳ Hạng phòng nào đủ số lượng yêu cầu -> Bỏ qua khách sạn này
                if (!$hasAvailableRoom) continue;

                if ($priceMax && $minPrice > $priceMax) {
                    continue;
                }

                $hotel->min_price = $minPrice;

                // Gộp tiện ích
                if ($hotel->amenities->count() === 0) {
                    $allAmenities = collect();
                    foreach ($hotel->roomTypes as $room) {
                        $allAmenities = $allAmenities->merge($room->amenities);
                    }
                    $hotel->amenities = $allAmenities->unique('id')->values();
                }
            } else {
                if ($priceMax) continue; // Nếu không có phòng mà có lọc giá thì bỏ qua
                $hotel->min_price = null;
            }

            unset($hotel->roomTypes);
            $filteredHotels->push($hotel);
        }

        return response()->json(['message' => 'Lấy danh sách thành công', 'data' => $filteredHotels->values()], 200);
    }

    public function getFiltersData()
    {
        $hotelAmenities = Amenity::where('type', 1)->get();
        $roomAmenities = Amenity::where('type', 2)->get();

        return response()->json([
            'message' => 'Lấy danh sách danh mục bộ lọc thành công',
            'data' => [
                'hotel_amenities' => $hotelAmenities,
                'room_amenities' => $roomAmenities
            ]
        ], 200);
    }

    public function getDetail(Request $request, int $id)
    {
        $checkIn = $request->query('checkIn');
        $checkOut = $request->query('checkOut');

        // 👉 ĐÃ THÊM: Lấy số lượng phòng yêu cầu (Từ URL)
        $requestedRooms = $request->query('rooms', 1);

        $hotel = Hotel::with(['images'])->find($id);
        if (!$hotel) {
            return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);
        }

        $roomTypes = RoomType::with(['media', 'amenities', 'roomView', 'bedTypeDetail'])
            ->where('hotel_id', $id)
            ->where('status', 1)
            ->get();

        if ($checkIn && $checkOut) {
            foreach ($roomTypes as $roomType) {
                $totalPhysicalRooms = Room::where('hotel_id', $id)
                    ->where('room_type_id', $roomType->id)
                    ->where('status', '!=', 0)
                    ->count();

                $bookedRoomsCount = BookingDetail::where('room_type_id', $roomType->id)
                    ->whereHas('booking', function ($query) use ($checkIn, $checkOut) {
                        $query->whereIn('status', [1, 2])
                            ->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn);
                    })->sum('rooms_count');

                $availableRooms = max(0, $totalPhysicalRooms - $bookedRoomsCount);
                $roomType->available_rooms = $availableRooms;
            }

            // 👉 SỬA LẠI ĐIỀU KIỆN LỌC (Lọc các phòng có đủ số lượng theo yêu cầu)
            $roomTypes = $roomTypes->filter(function ($room) use ($requestedRooms) {
                return $room->available_rooms >= $requestedRooms;
            })->values();
        }

        $hotel->room_types = $roomTypes;

        return response()->json(['message' => 'Lấy chi tiết thành công', 'data' => $hotel], 200);
    }

    public function getRoomMasterData()
    {
        $views = RoomView::where('status', 1)->get(['id', 'name']);
        $beds = BedType::where('status', 1)->get(['id', 'name']);

        return response()->json([
            'message' => 'Lấy danh mục thành công',
            'data' => [
                'room_views' => $views,
                'bed_types' => $beds
            ]
        ], 200);
    }
}
