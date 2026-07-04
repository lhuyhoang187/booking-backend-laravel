<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Supply; // Đảm bảo bạn đã có model Supply nhé
use Illuminate\Http\Request;

class SupplyController extends Controller
{
    public function getSupplies(Request $request)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        // Lấy từ bảng supplies thay vì services
        $supplies = Supply::where('hotel_id', $hotelId)->orderBy('id', 'desc')->get();
        return response()->json(['data' => $supplies], 200);
    }

    public function storeSupply(Request $request)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin khách sạn'], 400);

        $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric' // Frontend vẫn gửi 'price', ta map nó vào 'price_per_unit'
        ]);

        $supply = new Supply();
        $supply->hotel_id = $hotelId; // Dùng trực tiếp $hotelId
        $supply->name = $request->name;
        $supply->price_per_unit = $request->price; // Lưu vào đúng cột DB
        $supply->status = 1;
        $supply->save();

        return response()->json(['message' => 'Đã thêm vật tư thành công!'], 201);
    }

    public function updateSupply(Request $request, int $id)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();

        $supply = Supply::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$supply) return response()->json(['message' => 'Không tìm thấy vật tư'], 404);

        if ($request->has('name')) $supply->name = $request->name;
        if ($request->has('price')) $supply->price_per_unit = $request->price; // Lưu vào đúng cột DB
        if ($request->has('status')) $supply->status = $request->status;
        $supply->save();

        return response()->json(['message' => 'Cập nhật vật tư thành công!'], 200);
    }

    public function deleteSupply(int $id)
    {
        // 👉 Gọi hàm thông minh
        $hotelId = $this->getHotelId();

        $supply = Supply::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$supply) return response()->json(['message' => 'Không tìm thấy vật tư'], 404);

        $supply->status = 0; // Chuyển trạng thái về 0 thay vì xóa cứng
        $supply->save();

        return response()->json(['message' => 'Đã xóa vật tư!'], 200);
    }
}
