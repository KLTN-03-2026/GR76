<?php

namespace App\Services;

use App\Models\NguoiDung;
use App\Models\Admin;
use App\Models\VaiTro;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function registerUser(array $data)
    {
        $data['mat_khau'] = Hash::make($data['mat_khau']);
        
        if (!isset($data['id_vai_tro'])) {
            $role = VaiTro::where('ten_vai_tro', 'user')->first();
            $data['id_vai_tro'] = $role ? $role->id_vai_tro : 2;
        }
        
        $user = NguoiDung::create($data);
        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken
        ];
    }

    public function loginUser(array $credentials)
    {
        $user = NguoiDung::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['mat_khau'], $user->mat_khau)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }
        
        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken
        ];
    }

    public function loginAdmin(array $credentials)
    {
        $admin = Admin::where('ten_dang_nhap', $credentials['ten_dang_nhap'])->first();
        if (!$admin || !Hash::check($credentials['mat_khau'], $admin->mat_khau)) {
            throw ValidationException::withMessages([
                'ten_dang_nhap' => ['Thông tin đăng nhập Admin không chính xác.'],
            ]);
        }
        
        return [
            'user'  => array_merge($admin->toArray(), ['vai_tro' => 'admin']),
            'token' => $admin->createToken('admin_token')->plainTextToken
        ];
    }
}
