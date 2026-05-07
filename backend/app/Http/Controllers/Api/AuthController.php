<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Services\AuthService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Đăng ký tài khoản mới + tự động gửi OTP xác minh email.
     * POST /register
     */
    public function register(RegisterRequest $request)
    {
        $data = $this->authService->registerUser($request->validated());

        // Tự động gửi OTP xác minh email ngay sau khi đăng ký
        $email = $request->email;
        $otp   = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("otp_verify_{$email}", $otp, now()->addMinutes(15));
        Log::info("[OTP-VERIFY-REGISTER] {$email} => {$otp}");

        $otpDemo = null;
        try {
            Mail::to($email)->send(new OtpMail($otp, 'verify', $email));
        } catch (\Throwable $e) {
            Log::warning("[OTP-MAIL-FAIL] {$email}: " . $e->getMessage());
            $otpDemo = $otp; // Hiển thị OTP khi email chưa cấu hình
        }

        $response = [
            'message'  => 'Đăng ký thành công! Vui lòng xác minh email của bạn.',
            'data'     => $data,
        ];
        if ($otpDemo) {
            $response['otp_demo'] = $otpDemo;
        }

        return response()->json($response, 201);
    }

    /**
     * Đăng nhập user.
     * POST /login
     */
    public function login(LoginRequest $request)
    {
        $data = $this->authService->loginUser($request->validated());
        return response()->json([
            'message' => 'Đăng nhập thành công',
            'data'    => $data,
        ]);
    }

    /**
     * Đăng nhập admin.
     * POST /admin/login
     */
    public function loginAdmin(AdminLoginRequest $request)
    {
        $data = $this->authService->loginAdmin($request->validated());
        return response()->json([
            'message' => 'Admin đăng nhập thành công',
            'data'    => $data,
        ]);
    }

    /**
     * Đăng xuất.
     * POST /logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }
}
