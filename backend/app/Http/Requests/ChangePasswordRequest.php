<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mat_khau_cu' => 'required|string',
            'mat_khau_moi' => 'required|string|min:6|different:mat_khau_cu',
        ];
    }
}
