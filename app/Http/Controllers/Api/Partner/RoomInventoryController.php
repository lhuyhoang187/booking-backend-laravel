<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Models\RoomInventory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RoomInventoryController extends Controller
{
    // Lấy ma trận dữ liệu tồn kho & giá phòng theo dải ngày
    public function index(Request $request)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Không tìm thấy khách sạn.'], 404);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Tạo mảng danh sách ngày để gửi ra ngoài cho Angular làm Header cột
        $period = CarbonPeriod::create($startDate, $endDate);
        $daysHeader = [];
        foreach ($period as $date) {
            $daysHeader[] = [
                'date_string' => $date->format('Y-m-d'),
                'day_name' => $date->locale('vi')->minDayName, // Thứ 2, Thứ 3...
                'day_label' => $date->format('d/m')
            ];
        }

        // Lấy toàn bộ hạng phòng của khách sạn
        $roomTypes = RoomType::where('hotel_id', $hotelId)->where('status', 1)->get();

        // Lấy toàn bộ dữ liệu cấu hình đặc biệt trong dải ngày này
        $inventories = RoomInventory::whereIn('room_type_id', $roomTypes->pluck('id'))
            ->whereBetween('apply_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // 👉 ĐÃ FIX: Chuyển Collection thành mảng tra cứu (Lookup) để tránh lỗi so sánh Object Carbon và tăng tốc độ
        $inventoryLookup = [];
        foreach ($inventories as $inv) {
            $dateFormatted = $inv->apply_date instanceof Carbon
                ? $inv->apply_date->format('Y-m-d')
                : Carbon::parse($inv->apply_date)->format('Y-m-d');

            $key = $inv->room_type_id . '_' . $dateFormatted;
            $inventoryLookup[$key] = $inv;
        }

        $resultGrid = [];

        foreach ($roomTypes as $roomType) {
            $rowGrid = [
                'room_type_id' => $roomType->id,
                'room_type_name' => $roomType->name,
                'base_price' => $roomType->base_price,
                'days_data' => []
            ];

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                // 👉 ĐÃ FIX: Lấy dữ liệu từ mảng tra cứu cực nhanh và chính xác
                $lookupKey = $roomType->id . '_' . $dateStr;
                $customData = $inventoryLookup[$lookupKey] ?? null;

                // Tính toán tỷ lệ phần trăm và chiều hướng (Trend)
                $price = $customData ? (float)$customData->price : (float)$roomType->base_price;
                $basePrice = (float)$roomType->base_price;
                $isCustom = $customData ? true : false;

                $diff = $price - $basePrice;
                $percentChange = $basePrice > 0 ? round(($diff / $basePrice) * 100) : 0;

                $trend = 'none';
                if ($percentChange > 0) $trend = 'up';
                elseif ($percentChange < 0) $trend = 'down';

                $rowGrid['days_data'][] = [
                    'date' => $dateStr,
                    'price' => $price,
                    'is_closed' => $customData ? (int)$customData->is_closed : 0,
                    'is_custom' => $isCustom,
                    'percent_change' => $percentChange,
                    'trend' => $trend // 'up', 'down', 'none'
                ];
            }

            $resultGrid[] = $rowGrid;
        }

        return response()->json([
            'headers' => $daysHeader,
            'grid' => $resultGrid
        ], 200);
    }

    // Cập nhật giá trị (Bulk Update) khi kéo chọn hoặc lưu ô
    public function updateBulk(Request $request)
    {
        $request->validate([
            'room_type_ids' => 'required|array',
            'room_type_ids.*' => 'integer|exists:room_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',

            // Cấu hình cập nhật nâng cao
            'update_type' => 'required|string|in:fixed,percent,reset',
            'price_value' => 'nullable|numeric',
            'change_status' => 'required|boolean',
            'is_closed' => 'nullable|boolean'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($request->room_type_ids as $roomTypeId) {
            $roomType = RoomType::find($roomTypeId);
            if (!$roomType) continue;

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                // Chế độ RESET: Khôi phục trạng thái mặc định của hệ thống
                if ($request->update_type === 'reset') {
                    $inventory = RoomInventory::where('room_type_id', $roomTypeId)
                        ->where('apply_date', $dateStr)
                        ->first();

                    if ($inventory) {
                        if ($request->change_status) {
                            $inventory->update([
                                'price' => $roomType->base_price,
                                'is_closed' => $request->is_closed ? 1 : 0
                            ]);
                        } else {
                            $inventory->delete();
                        }
                    }
                    continue;
                }

                // Chế độ FIXED hoặc PERCENT
                $inventory = RoomInventory::firstOrNew([
                    'room_type_id' => $roomTypeId,
                    'apply_date' => $dateStr
                ]);

                // Xử lý tính toán giá động 
                if ($request->update_type === 'fixed' && !is_null($request->price_value)) {
                    $inventory->price = $request->price_value;
                } elseif ($request->update_type === 'percent' && !is_null($request->price_value)) {
                    $currentBase = $inventory->exists ? $inventory->price : $roomType->base_price;
                    $calculatedPrice = $currentBase + ($currentBase * ($request->price_value / 100));
                    $inventory->price = max(0, $calculatedPrice);
                } elseif (!$inventory->exists) {
                    $inventory->price = $roomType->base_price;
                }

                if ($request->change_status) {
                    $inventory->is_closed = $request->is_closed ? 1 : 0;
                }

                $inventory->available_allotment = 99; // Giữ Allotment mặc định
                $inventory->save();
            }
        }

        return response()->json(['message' => 'Cập nhật lịch phòng thành công!'], 200);
    }
}
