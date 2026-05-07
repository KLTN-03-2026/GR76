<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $type === 'reset' ? 'Đặt lại mật khẩu' : 'Xác minh email' }} – SOS System</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #1a1a2e; }
    .wrapper { max-width: 540px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10); }
    .header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 36px 32px 28px; text-align: center; }
    .header .logo { font-size: 32px; margin-bottom: 8px; }
    .header h1 { color: #fff; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
    .header p { color: rgba(255,255,255,0.85); font-size: 13px; margin-top: 4px; }
    .body { padding: 36px 32px; }
    .greeting { font-size: 16px; color: #333; margin-bottom: 16px; }
    .desc { font-size: 14px; color: #555; line-height: 1.7; margin-bottom: 28px; }
    .otp-box { background: #f8f9ff; border: 2px dashed #e74c3c; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 28px; }
    .otp-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
    .otp-code { font-size: 42px; font-weight: 800; letter-spacing: 14px; color: #e74c3c; font-family: 'Courier New', monospace; }
    .expire-note { font-size: 12px; color: #aaa; margin-top: 10px; }
    .warning { background: #fff8e1; border-left: 4px solid #f39c12; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; font-size: 13px; color: #7d5800; }
    .warning strong { color: #e67e22; }
    .steps { margin-bottom: 28px; }
    .steps h3 { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 10px; }
    .step { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; font-size: 13px; color: #555; }
    .step-num { background: #e74c3c; color: #fff; font-size: 11px; font-weight: 700; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }
    .divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }
    .footer { background: #f8f9fb; padding: 20px 32px; text-align: center; border-top: 1px solid #eee; }
    .footer p { font-size: 12px; color: #aaa; line-height: 1.6; }
    .footer .brand { font-weight: 700; color: #e74c3c; }
  </style>
</head>
<body>
<div class="wrapper">

  <!-- Header -->
  <div class="header">
    <div class="logo">🚨</div>
    <h1>SOS System</h1>
    <p>Hệ thống quản lý sự cố khẩn cấp</p>
  </div>

  <!-- Body -->
  <div class="body">
    <p class="greeting">Xin chào,</p>

    @if ($type === 'reset')
      <p class="desc">
        Chúng tôi nhận được yêu cầu <strong>đặt lại mật khẩu</strong> cho tài khoản của bạn trên SOS System.
        Sử dụng mã OTP bên dưới để tiến hành đặt lại mật khẩu.
      </p>
    @else
      <p class="desc">
        Cảm ơn bạn đã đăng ký tài khoản trên <strong>SOS System</strong>.
        Để hoàn tất quá trình đăng ký, vui lòng xác minh địa chỉ email bằng mã OTP bên dưới.
      </p>
    @endif

    <!-- OTP Box -->
    <div class="otp-box">
      <div class="otp-label">Mã xác nhận OTP của bạn</div>
      <div class="otp-code">{{ $otp }}</div>
      <div class="expire-note">
        ⏱ Mã có hiệu lực trong <strong>{{ $type === 'reset' ? '10' : '15' }} phút</strong>
      </div>
    </div>

    <!-- Steps -->
    <div class="steps">
      <h3>📋 Hướng dẫn sử dụng:</h3>
      @if ($type === 'reset')
        <div class="step"><span class="step-num">1</span> Quay lại trang Quên mật khẩu trên SOS System</div>
        <div class="step"><span class="step-num">2</span> Nhập mã OTP <strong>{{ $otp }}</strong> vào ô xác nhận</div>
        <div class="step"><span class="step-num">3</span> Tạo mật khẩu mới cho tài khoản của bạn</div>
      @else
        <div class="step"><span class="step-num">1</span> Quay lại trang đăng ký trên SOS System</div>
        <div class="step"><span class="step-num">2</span> Nhập mã OTP <strong>{{ $otp }}</strong> vào ô xác minh email</div>
        <div class="step"><span class="step-num">3</span> Tài khoản của bạn sẽ được kích hoạt ngay lập tức</div>
      @endif
    </div>

    <!-- Security warning -->
    <div class="warning">
      <strong>⚠️ Lưu ý bảo mật:</strong> Không chia sẻ mã OTP này với bất kỳ ai.
      Nhân viên SOS System sẽ không bao giờ yêu cầu mã OTP của bạn.
      Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email này.
    </div>

    <hr class="divider" />
    <p style="font-size:13px;color:#888;text-align:center;">
      Email này được gửi tự động. Vui lòng không trả lời email này.
    </p>
  </div>

  <!-- Footer -->
  <div class="footer">
    <p>© {{ date('Y') }} <span class="brand">SOS System</span> – Hệ thống quản lý sự cố khẩn cấp</p>
    <p style="margin-top:4px;">Được phát triển bởi nhóm nghiên cứu – Đồ án tốt nghiệp 2026</p>
  </div>

</div>
</body>
</html>
