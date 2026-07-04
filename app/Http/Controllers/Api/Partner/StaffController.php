<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    private function checkIsOwner()
    {
        $user = Auth::guard('partner')->user();
        return $user && $user->role_id == 1; // 1 luôn là Owner
    }

    public function index()
    {
        if (!$this->checkIsOwner()) return response()->json(['message' => 'Không có quyền truy cập!'], 403);

        $ownerId = $this->getOwnerId();

        // Kéo theo thông tin tên Nhóm quyền (role.name)
        $staffs = Partner::with('role:id,name')
            ->where('parent_id', $ownerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['message' => 'Thành công', 'data' => $staffs], 200);
    }

    public function store(Request $request)
    {
        if (!$this->checkIsOwner()) return response()->json(['message' => 'Không có quyền truy cập!'], 403);

        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:partners,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:15',
            'role_id' => 'required|integer|exists:partner_roles,id', // 👉 PHẢI LÀ ID CỦA BẢNG partner_roles
        ]);

        $ownerId = $this->getOwnerId();

        // Đảm bảo role_id được chọn thuộc về đúng Owner này
        $roleExists = \App\Models\PartnerRole::where('id', $request->role_id)->where('owner_id', $ownerId)->exists();
        if (!$roleExists) return response()->json(['message' => 'Nhóm quyền không hợp lệ!'], 400);

        $staff = Partner::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'parent_id' => $ownerId,
            'is_active' => 1
        ]);

        return response()->json(['message' => 'Tạo tài khoản nhân viên thành công!', 'data' => $staff], 201);
    }

    public function update(Request $request, int $id)
    {
        if (!$this->checkIsOwner()) return response()->json(['message' => 'Không có quyền truy cập!'], 403);

        $ownerId = $this->getOwnerId();
        $staff = Partner::where('id', $id)->where('parent_id', $ownerId)->firstOrFail();

        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:15',
            'role_id' => 'required|integer|exists:partner_roles,id',
            'is_active' => 'required|boolean'
        ]);

        $staff->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'is_active' => $request->is_active
        ]);

        if ($request->filled('password')) {
            $staff->update(['password_hash' => Hash::make($request->password)]);
        }

        return response()->json(['message' => 'Cập nhật nhân viên thành công!'], 200);
    }

    public function destroy(int $id)
    {
        if (!$this->checkIsOwner()) return response()->json(['message' => 'Không có quyền truy cập!'], 403);
        $ownerId = $this->getOwnerId();
        $staff = Partner::where('id', $id)->where('parent_id', $ownerId)->firstOrFail();
        $staff->delete();
        return response()->json(['message' => 'Đã xóa tài khoản nhân viên!'], 200);
    }

    // Thêm hàm lấy danh sách Role để đổ vào Dropdown ở Frontend
    public function getRoles()
    {
        if (!$this->checkIsOwner()) return response()->json(['message' => 'Không có quyền truy cập!'], 403);

        $ownerId = $this->getOwnerId();
        $roles = \App\Models\PartnerRole::where('owner_id', $ownerId)->get();

        return response()->json(['data' => $roles], 200);
    }
}
