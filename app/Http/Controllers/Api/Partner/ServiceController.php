<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // === 1. DỊCH VỤ KHÁCH SẠN (TYPE 1) ===
    public function getServices(Request $request)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin'], 400);

        $services = Service::where('hotel_id', $hotelId)->where('type', 1)->orderBy('id', 'desc')->get();
        return response()->json(['data' => $services], 200);
    }

    public function storeService(Request $request)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin'], 400);

        $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
        ]);

        $service = new Service();
        $service->hotel_id = $hotelId;
        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->unit = $request->unit;
        $service->icon = $request->icon;
        $service->type = 1;
        $service->status = $request->status ?? 1;
        $service->save();

        return response()->json(['message' => 'Thêm dịch vụ thành công!'], 201);
    }

    public function updateService(Request $request, int $id)
    {
        $hotelId = $this->getHotelId();

        $service = Service::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$service) return response()->json(['message' => 'Không tìm thấy dịch vụ'], 404);

        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->unit = $request->unit ?? 'Lượt';
        $service->icon = $request->icon;
        $service->status = $request->status ?? 1;
        $service->save();

        return response()->json(['message' => 'Cập nhật thành công!'], 200);
    }

    public function deleteService(int $id)
    {
        $hotelId = $this->getHotelId();

        $service = Service::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$service) return response()->json(['message' => 'Không tìm thấy dịch vụ'], 404);

        $service->status = 0;
        $service->save();

        return response()->json(['message' => 'Đã ngừng cung cấp dịch vụ này!'], 200);
    }

    // === 2. MINIBAR (TYPE 2) ===
    public function getMinibars(Request $request)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin'], 400);

        $minibars = Service::where('hotel_id', $hotelId)->where('type', 2)->orderBy('id', 'desc')->get();
        return response()->json(['data' => $minibars], 200);
    }

    public function storeMinibar(Request $request)
    {
        $hotelId = $this->getHotelId();
        if (!$hotelId) return response()->json(['message' => 'Chưa có thông tin'], 400);

        $request->validate(['name' => 'required', 'price' => 'required|numeric', 'quantity' => 'required|integer']);

        $service = new Service();
        $service->hotel_id = $hotelId;
        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->quantity = $request->quantity;
        $service->icon = $request->icon;
        $service->type = 2;
        $service->status = $request->status ?? 1;
        $service->save();

        return response()->json(['message' => 'Thêm món Minibar thành công!'], 201);
    }

    public function updateMinibar(Request $request, int $id)
    {
        $hotelId = $this->getHotelId();

        $service = Service::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$service) return response()->json(['message' => 'Không tìm thấy'], 404);

        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;
        $service->quantity = $request->quantity ?? 0;
        $service->icon = $request->icon;
        $service->status = $request->status ?? 1;
        $service->save();

        return response()->json(['message' => 'Cập nhật thành công!'], 200);
    }

    public function deleteMinibar(int $id)
    {
        $hotelId = $this->getHotelId();

        $service = Service::where('id', $id)->where('hotel_id', $hotelId)->first();
        if (!$service) return response()->json(['message' => 'Không tìm thấy'], 404);

        $service->status = 0;
        $service->save();

        return response()->json(['message' => 'Đã ngừng kinh doanh món này!'], 200);
    }
}
