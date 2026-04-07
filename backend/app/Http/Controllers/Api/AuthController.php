<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $data = $this->authService->registerUser($request->validated());
        return response()->json([
            'message' => 'Đăng ký thành công',
            'data' => $data
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $this->authService->loginUser($request->validated());
        return response()->json([
            'message' => 'Đăng nhập thành công',
            'data' => $data
        ]);
    }

    public function loginAdmin(AdminLoginRequest $request)
    {
        $data = $this->authService->loginAdmin($request->validated());
        return response()->json([
            'message' => 'Admin đăng nhập thành công',
            'data' => $data
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Đăng xuất thành công'
        ]);
    }
}
