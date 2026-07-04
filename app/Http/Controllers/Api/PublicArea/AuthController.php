<?php

namespace App\Http\Controllers\Api\PublicArea;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Partner;
use App\Models\Customer;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    // ==========================================
    // 1. ĐĂNG KÝ KHÁCH HÀNG
    // ==========================================
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:customers,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $nameParts = explode(' ', trim($request->name));
        $firstName = array_pop($nameParts);
        $lastName = empty($nameParts) ? $firstName : implode(' ', $nameParts);

        $customer = Customer::create([
            'last_name' => $lastName,
            'first_name' => $firstName,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'is_active' => 1,
            'created_at' => now()
        ]);

        $token = $customer->createToken('customer_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng ký tài khoản khách hàng thành công!',
            'user' => $customer,
            'token' => $token,
            'role' => 'customer'
        ], 201);
    }

    // ==========================================
    // 2. ĐĂNG KÝ ĐỐI TÁC KHÁCH SẠN
    // ==========================================
    public function registerPartner(Request $request)
    {
        $request->validate([
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:50',
            'email' => 'required|string|email|unique:partners,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:15',
            'hotel_name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $partner = Partner::create([
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => 0, // Mặc định chờ duyệt
            ]);

            Hotel::create([
                'partner_id' => $partner->id,
                'name' => $request->hotel_name,
                'city' => $request->city,
                'address' => $request->address,
                'status' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Đăng ký thành công! Vui lòng chờ Admin phê duyệt.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi quá trình đăng ký: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // 3. ĐĂNG NHẬP (Dùng chung 3 Role)
    // ==========================================
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'type' => 'required|in:admin,partner,customer'
        ]);

        // Cú pháp match (PHP 8+) siêu gọn gàng
        $model = match ($request->type) {
            'admin' => Admin::class,
            'partner' => Partner::class,
            'customer' => Customer::class,
        };

        $user = $model::where('email', $request->email)->first();

        // Kiểm tra thủ công do dùng cột password_hash
        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác!'
            ], 401);
        }

        // Khách hàng không cần duyệt, chỉ kiểm tra is_active cho admin/partner
        if (isset($user->is_active) && $user->is_active == 0) {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa hoặc đang chờ duyệt!'
            ], 403);
        }

        // Tạo Token Sanctum
        $token = $user->createToken($request->type . '_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'user' => $user,
            'token' => $token,
            'role' => $request->type
        ], 200);
    }

    // ==========================================
    // 4. ĐĂNG XUẤT (Hủy Token) -> MỚI BỔ SUNG
    // ==========================================
    public function logout(Request $request)
    {
        // Xóa Token hiện tại đang được sử dụng để gọi API này
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công!'
        ], 200);
    }
}
