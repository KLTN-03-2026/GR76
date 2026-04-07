<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMucDoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_muc_do' => 'required|string|max:255|unique:muc_do_khan_caps,ten_muc_do',
            'do_uu_tien' => 'required|integer|min:0',
        ];
    }
}
