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
            'token' => null
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
        
        if (is_null($user->email_verified_at)) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản chưa được xác minh. Vui lòng kiểm tra email để kích hoạt.'],
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

    public function loginUnified(array $credentials)
    {
        $identifier = $credentials['identifier'];
        $password = $credentials['mat_khau'];

        // Try Admin
        $admin = Admin::where('ten_dang_nhap', $identifier)
                    ->orWhere('email', $identifier)
                    ->first();
        if ($admin && Hash::check($password, $admin->mat_khau)) {
            return [
                'user'  => array_merge($admin->toArray(), ['vai_tro' => 'admin']),
                'token' => $admin->createToken('admin_token')->plainTextToken,
                'type'  => 'admin'
            ];
        }

        // Try User
        $user = NguoiDung::where('email', $identifier)->first();
        if ($user && Hash::check($password, $user->mat_khau)) {
            if (is_null($user->email_verified_at)) {
                throw ValidationException::withMessages([
                    'identifier' => ['Tài khoản chưa được xác minh. Vui lòng kiểm tra email để kích hoạt.'],
                ]);
            }
            return [
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
                'type' => 'user'
            ];
        }

        throw ValidationException::withMessages([
            'identifier' => ['Thông tin đăng nhập không chính xác.'],
        ]);
    }
}
