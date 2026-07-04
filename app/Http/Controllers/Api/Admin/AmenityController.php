<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Amenity;

class AmenityController extends Controller
{
    // 1. GET /api/admin/amenities (Lấy danh sách tiện ích)
    public function index()
    {
        $amenities = Amenity::orderBy('id', 'desc')->get();
        return response()->json(['data' => $amenities], 200);
    }

    // 2. POST /api/admin/amenities (Thêm tiện ích mới)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|integer|in:1,2' // 1: Hotel, 2: Room
        ]);

        $amenity = new Amenity();
        $amenity->name = $request->name;
        $amenity->icon = $request->icon;
        $amenity->type = $request->type;
        $amenity->save();

        return response()->json(['message' => 'Thêm tiện ích thành công!', 'data' => $amenity], 201);
    }

    // 3. PUT /api/admin/amenities/{id} (Cập nhật tiện ích)
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|integer|in:1,2'
        ]);

        $amenity = Amenity::findOrFail($id);
        $amenity->name = $request->name;
        $amenity->icon = $request->icon;
        $amenity->type = $request->type;
        $amenity->save();

        return response()->json(['message' => 'Cập nhật tiện ích thành công!'], 200);
    }

    // 4. DELETE /api/admin/amenities/{id} (Xóa tiện ích)
    public function destroy(int $id)
    {
        try {
            $amenity = Amenity::findOrFail($id);
            $amenity->delete();
            return response()->json(['message' => 'Xóa tiện ích thành công!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Không thể xóa vì tiện ích này đang được sử dụng!'], 400);
        }
    }
}