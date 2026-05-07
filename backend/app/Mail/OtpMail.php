<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $type;   // 'verify' | 'reset'
    public string $email;

    public function __construct(string $otp, string $type = 'verify', string $email = '')
    {
        $this->otp   = $otp;
        $this->type  = $type;
        $this->email = $email;
    }

    public function envelope(): Envelope
    {
        $subject = $this->type === 'reset'
            ? '[SOS System] 🔑 Mã OTP đặt lại mật khẩu'
            : '[SOS System] ✉️ Xác minh địa chỉ email';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }

    public function attachments(): array
    {
        return [];
    }
}
