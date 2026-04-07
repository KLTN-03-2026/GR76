<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoaiSuCoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_loai' => 'required|string|max:255|unique:loai_su_cos,ten_loai',
        ];
    }
}
