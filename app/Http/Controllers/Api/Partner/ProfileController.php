<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // Lấy thông tin cá nhân
    public function getProfile()
    {
        $partner = Auth::guard('partner')->user();

        if (!$partner) {
            return response()->json(['message' => 'Không tìm thấy thông tin tài khoản'], 404);
        }

        return response()->json([
            'message' => 'Lấy thông tin thành công',
            'data' => $partner
        ], 200);
    }

    // Cập nhật thông tin (Chỉ cho phép đổi Tên, Họ và Số điện thoại)
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\Partner $partner */
        $partner = Auth::guard('partner')->user();

        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:15',
        ]);

        $partner->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            // Không cho phép update email ở đây để đảm bảo bảo mật
        ]);

        return response()->json([
            'message' => 'Cập nhật hồ sơ thành công!',
            'data' => $partner
        ], 200);
    }

    // Đổi mật khẩu
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed', // 'confirmed' bắt buộc phải có trường 'new_password_confirmation' gửi lên
        ], [
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.'
        ]);

        /** @var \App\Models\Partner $partner */
        $partner = Auth::guard('partner')->user();

        // Kiểm tra mật khẩu cũ (Chú ý: So sánh với cột password_hash)
        if (!Hash::check($request->current_password, $partner->password_hash)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không chính xác!'
            ], 400);
        }

        // Kiểm tra mật khẩu mới không được trùng mật khẩu cũ
        if (Hash::check($request->new_password, $partner->password_hash)) {
            return response()->json([
                'message' => 'Mật khẩu mới không được giống mật khẩu hiện tại!'
            ], 400);
        }

        // Cập nhật mật khẩu mới
        $partner->password_hash = Hash::make($request->new_password);
        $partner->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công! Vui lòng sử dụng mật khẩu mới cho lần đăng nhập sau.'
        ], 200);
    }
}
