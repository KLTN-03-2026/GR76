<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten'           => 'required|string|min:2|max:100',
            'email'         => 'required|email|unique:nguoi_dungs,email',
            'so_dien_thoai' => ['required', 'string', 'regex:/^0[0-9]{9}$/', 'unique:nguoi_dungs,so_dien_thoai'],
            'mat_khau'      => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'ten.required'           => 'Họ tên là bắt buộc.',
            'ten.min'                => 'Họ tên phải ít nhất 2 ký tự.',
            'email.required'         => 'Email là bắt buộc.',
            'email.email'            => 'Email không hợp lệ.',
            'email.unique'           => 'Email này đã được đăng ký. Vui lòng dùng email khác.',
            'so_dien_thoai.required' => 'Số điện thoại là bắt buộc.',
            'so_dien_thoai.regex'    => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.',
            'so_dien_thoai.unique'   => 'Số điện thoại này đã được đăng ký.',
            'mat_khau.required'      => 'Mật khẩu là bắt buộc.',
            'mat_khau.min'           => 'Mật khẩu phải ít nhất 6 ký tự.',
        ];
    }
}
