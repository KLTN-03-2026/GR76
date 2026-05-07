<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // PASSWORD RESET (Quên mật khẩu)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Gửi mã OTP đặt lại mật khẩu về email.
     * POST /forgot-password
     */
    public function sendOtp(Request $request)
    {
        $request->validate(
            ['email' => 'required|email|exists:nguoi_dungs,email'],
            ['email.exists' => 'Email không tồn tại trong hệ thống.']
        );

        $email = $request->email;
        $otp   = $this->generateOtp();

        Cache::put("otp_reset_{$email}", $otp, now()->addMinutes(10));
        Log::info("[OTP-RESET] {$email} => {$otp}");

        return $this->sendMail($email, $otp, 'reset');
    }

    /**
     * Xác minh OTP và đặt lại mật khẩu mới.
     * POST /reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email|exists:nguoi_dungs,email',
            'otp'                   => 'required|digits:6',
            'mat_khau'              => 'required|string|min:6|confirmed',
            'mat_khau_confirmation' => 'required',
        ], [
            'email.exists'       => 'Email không tồn tại.',
            'otp.digits'         => 'OTP phải là 6 chữ số.',
            'mat_khau.min'       => 'Mật khẩu phải ít nhất 6 ký tự.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $email     = $request->email;
        $otp       = $request->otp;
        $cachedOtp = Cache::get("otp_reset_{$email}");

        if (!$cachedOtp) {
            return response()->json(['message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.'], 422);
        }
        if ($cachedOtp !== $otp) {
            return response()->json(['message' => 'Mã OTP không đúng. Vui lòng kiểm tra lại.'], 422);
        }

        $user = NguoiDung::where('email', $email)->firstOrFail();
        $user->update(['mat_khau' => Hash::make($request->mat_khau)]);

        Cache::forget("otp_reset_{$email}");
        $user->tokens()->delete(); // Thu hồi tất cả token bảo mật

        return response()->json(['message' => 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.']);
    }

    // ─────────────────────────────────────────────────────────────────
    // EMAIL VERIFICATION (Xác minh email khi đăng ký)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Gửi mã OTP xác minh email khi đăng ký.
     * POST /send-verify-email
     */
    public function sendVerifyEmail(Request $request)
    {
        $request->validate(
            ['email' => 'required|email'],
            ['email.required' => 'Email là bắt buộc.']
        );

        $email = $request->email;
        $otp   = $this->generateOtp();

        Cache::put("otp_verify_{$email}", $otp, now()->addMinutes(15));
        Log::info("[OTP-VERIFY] {$email} => {$otp}");

        return $this->sendMail($email, $otp, 'verify');
    }

    /**
     * Xác minh OTP email sau khi đăng ký.
     * POST /verify-email
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $email     = $request->email;
        $otp       = $request->otp;
        $cachedOtp = Cache::get("otp_verify_{$email}");

        if (!$cachedOtp) {
            return response()->json(['message' => 'Mã OTP đã hết hạn. Vui lòng nhấn "Gửi lại mã".'], 422);
        }
        if ($cachedOtp !== $otp) {
            return response()->json(['message' => 'Mã OTP không đúng. Vui lòng kiểm tra email và thử lại.'], 422);
        }

        NguoiDung::where('email', $email)->update(['email_verified_at' => now()]);
        Cache::forget("otp_verify_{$email}");

        return response()->json(['message' => 'Xác minh email thành công! Tài khoản đã được kích hoạt.']);
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────

    /** Tạo mã OTP 6 chữ số ngẫu nhiên */
    private function generateOtp(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Gửi email OTP qua Gmail SMTP.
     * Nếu gửi thất bại → trả về otp_demo cho môi trường dev.
     */
    private function sendMail(string $email, string $otp, string $type)
    {
        $expires = $type === 'reset' ? '10 phút' : '15 phút';

        try {
            Mail::to($email)->send(new OtpMail($otp, $type, $email));

            return response()->json([
                'message' => "Mã OTP đã được gửi đến {$email}. Kiểm tra hộp thư (và thư mục spam).",
                'expires_in' => $expires,
            ]);
        } catch (\Throwable $e) {
            Log::warning("[OTP-MAIL-FAIL] {$email}: " . $e->getMessage());

            // Fallback: trả OTP trong response khi email chưa được cấu hình
            return response()->json([
                'message'    => "Không gửi được email (lỗi SMTP). Dùng mã dưới đây để tiếp tục.",
                'otp_demo'   => $otp,
                'expires_in' => $expires,
                'error_hint' => $e->getMessage(),
            ]);
        }
    }
}
