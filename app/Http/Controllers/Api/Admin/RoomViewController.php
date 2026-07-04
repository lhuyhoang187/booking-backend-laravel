<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomView;

class RoomViewController extends Controller
{
    // 1. GET /api/admin/room-views (Lấy danh sách)
    public function index()
    {
        $views = RoomView::orderBy('id', 'desc')->get();
        return response()->json(['message' => 'Thành công', 'data' => $views], 200);
    }

    // 2. POST /api/admin/room-views (Tạo mới)
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $view = RoomView::create([
            'name' => $request->name,
            'status' => $request->status ?? 1
        ]);
        return response()->json(['message' => 'Thêm hướng nhìn thành công', 'data' => $view], 201);
    }

    // 3. GET /api/admin/room-views/{id} (Lấy chi tiết 1 cái)
    public function show(int $id)
    {
        $view = RoomView::find($id);
        if (!$view) return response()->json(['message' => 'Không tìm thấy'], 404);
        return response()->json(['message' => 'Thành công', 'data' => $view], 200);
    }

    // 4. PUT /api/admin/room-views/{id} (Cập nhật)
    public function update(Request $request, int $id)
    {
        $view = RoomView::find($id);
        if (!$view) return response()->json(['message' => 'Không tìm thấy'], 404);

        $view->update($request->only(['name', 'status']));
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $view], 200);
    }

    // 5. DELETE /api/admin/room-views/{id} (Xóa)
    public function destroy(int $id)
    {
        $view = RoomView::find($id);
        if (!$view) return response()->json(['message' => 'Không tìm thấy'], 404);

        $view->delete();
        return response()->json(['message' => 'Xóa thành công'], 200);
    }
}
