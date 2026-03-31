<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ==========================================
    // CÁC API KHÔNG CẦN ĐĂNG NHẬP
    // ==========================================

    // API 1: Đăng ký tài khoản Đối tác khách sạn
    public function registerPartner(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:15',
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password), 
            'phone' => $request->phone,
            'is_active' => 1,
            'created_at' => now()
        ]);

        $token = $user->createToken('partner_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng ký tài khoản đối tác thành công!',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // API 2: Đăng nhập
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác!'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    // ==========================================
    // CÁC API BẮT BUỘC PHẢI ĐĂNG NHẬP (CÓ TOKEN)
    // ==========================================

    // API 3: Xem thông tin cá nhân
    public function getProfile(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'message' => 'Lấy thông tin thành công',
            'user' => $user
        ], 200);
    }

    // API 4: Cập nhật thông tin liên hệ
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'full_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:15',
        ]);

        $user->update([
            'full_name' => $request->full_name,
            'phone' => $request->phone
        ]);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công!',
            'user' => $user
        ], 200);
    }

    // API 5: Đổi mật khẩu
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