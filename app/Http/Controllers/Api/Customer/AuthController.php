<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function getProfile(Request $request)
    {
        return response()->json([
            'message' => 'Lấy thông tin thành công',
            'user' => $request->user()
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:15',
        ];

        if ($user instanceof Customer) {
            $rules['gender'] = 'nullable|string|max:10';
            $rules['dob'] = 'nullable|date';
            $rules['address'] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $updateData = [
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'phone' => $request->phone,
        ];

        if ($user instanceof Customer) {
            if ($request->has('gender')) $updateData['gender'] = $request->gender;
            if ($request->has('dob')) $updateData['dob'] = $request->dob;
            if ($request->has('address')) $updateData['address'] = $request->address;
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công!',
            'user' => $user
        ], 200);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if ($user instanceof Admin) {
            return response()->json([
                'message' => 'Không thể xóa tài khoản Quản trị viên hệ thống!'
            ], 403);
        }

        $user->update(['is_active' => 0]);
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Tài khoản đã được xóa thành công!'], 200);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        if (!Hash::check($request->current_password, $user->password_hash)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không chính xác!'
            ], 400);
        }

        $user->update([
            'password_hash' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công!'
        ], 200);
    }
}