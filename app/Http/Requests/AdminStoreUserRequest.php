<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreUserRequest extends FormRequest
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
            'id_vai_tro' => 'required|exists:vai_tros,id_vai_tro',
        ];
    }
}
