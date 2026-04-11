<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Http\Requests\AdminStoreUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => NguoiDung::with('vaiTro')->get()]);
    }

    public function store(AdminStoreUserRequest $request)
    {
        $data = $request->validated();
        $data['mat_khau'] = Hash::make($data['mat_khau']);
        $user = NguoiDung::create($data);
        return response()->json(['message' => 'Người dùng đã được tạo', 'data' => $user], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => NguoiDung::with('vaiTro')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $user = NguoiDung::findOrFail($id);
        $data = $request->validate([
            'ten' => 'sometimes|string|min:3|max:50',
            'email' => ['sometimes', 'email', Rule::unique('nguoi_dungs')->ignore($id, 'id_nguoi_dung')],
            'so_dien_thoai' => ['sometimes', 'string', 'size:10', Rule::unique('nguoi_dungs')->ignore($id, 'id_nguoi_dung')],
            'id_vai_tro' => 'sometimes|exists:vai_tros,id_vai_tro',
        ]);
        $user->update($data);
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $user]);
    }

    public function destroy($id)
    {
        $user = NguoiDung::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        $users = NguoiDung::where('ten', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('so_dien_thoai', 'like', "%{$q}%")
            ->get();
        return response()->json(['data' => $users]);
    }

    public function changePassword(Request $request, $id)
    {
        $user = NguoiDung::findOrFail($id);
        $request->validate(['mat_khau_moi' => 'required|string|min:6']);
        $user->update(['mat_khau' => Hash::make($request->mat_khau_moi)]);
        return response()->json(['message' => 'Đổi mật khẩu thành công']);
    }

    public function lock($id)
    {
        $user = NguoiDung::findOrFail($id);
        $user->update(['trang_thai' => 'bi_khoa']);
        return response()->json(['message' => 'Đã khóa tài khoản thành công', 'data' => $user]);
    }

    public function unlock($id)
    {
        $user = NguoiDung::findOrFail($id);
        $user->update(['trang_thai' => 'hoat_dong']);
        return response()->json(['message' => 'Đã mở khóa tài khoản thành công', 'data' => $user]);
    }
}
