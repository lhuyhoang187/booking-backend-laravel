<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    // 1. Lấy danh sách khách hàng (Từ bảng customers)
    public function index()
    {
        $customers = Customer::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $customers], 200);
    }

    // 2. Khóa / Mở khóa tài khoản khách hàng
    public function toggleStatus(int $userId)
    {
        try {
            $customer = Customer::findOrFail($userId);

            // Đảo ngược trạng thái: Nếu đang 1 (hoạt động) thì thành 0 (khóa), và ngược lại
            $customer->is_active = $customer->is_active == 1 ? 0 : 1;
            $customer->save();

            $statusText = $customer->is_active == 1 ? 'MỞ KHÓA' : 'KHÓA';
            return response()->json(['message' => "Đã $statusText tài khoản khách hàng thành công!"], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }
}