<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BedType;

class BedTypeController extends Controller
{
    // 1. GET /api/admin/bed-types
    public function index()
    {
        $beds = BedType::orderBy('id', 'desc')->get();
        return response()->json(['message' => 'Thành công', 'data' => $beds], 200);
    }

    // 2. POST /api/admin/bed-types
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $bed = BedType::create([
            'name' => $request->name,
            'status' => $request->status ?? 1
        ]);
        return response()->json(['message' => 'Thêm loại giường thành công', 'data' => $bed], 201);
    }

    // 3. GET /api/admin/bed-types/{id}
    public function show(int $id)
    {
        $bed = BedType::find($id);
        if (!$bed) return response()->json(['message' => 'Không tìm thấy'], 404);
        return response()->json(['message' => 'Thành công', 'data' => $bed], 200);
    }

    // 4. PUT /api/admin/bed-types/{id}
    public function update(Request $request, int $id)
    {
        $bed = BedType::find($id);
        if (!$bed) return response()->json(['message' => 'Không tìm thấy'], 404);

        $bed->update($request->only(['name', 'status']));
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $bed], 200);
    }

    // 5. DELETE /api/admin/bed-types/{id}
    public function destroy(int $id)
    {
        $bed = BedType::find($id);
        if (!$bed) return response()->json(['message' => 'Không tìm thấy'], 404);

        $bed->delete();
        return response()->json(['message' => 'Xóa thành công'], 200);
    }
}
