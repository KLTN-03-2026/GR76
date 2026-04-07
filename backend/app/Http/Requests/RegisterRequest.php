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
            'ten' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:nguoi_dungs,email',
            'so_dien_thoai' => 'required|string|size:10|unique:nguoi_dungs,so_dien_thoai',
            'mat_khau' => 'required|string|min:6',
        ];
    }
}
