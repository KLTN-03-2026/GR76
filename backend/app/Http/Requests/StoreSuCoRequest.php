<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuCoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tieu_de'         => 'required|string|min:5|max:200',
            'noi_dung'        => 'required|string|min:10',
            'dia_chi'         => 'required|string|min:5|max:255',
            'vi_do'           => 'nullable|numeric',       // nullable — GPS optional
            'kinh_do'         => 'nullable|numeric',
            'id_loai_su_co'   => 'required|exists:loai_su_cos,id_loai_su_co',
            'id_muc_do'       => 'required|exists:muc_do_khan_caps,id_muc_do',
            'hinh_anh'        => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240', // 10MB
            'hinh_anhs'       => 'nullable|array|max:5',
            'hinh_anhs.*'     => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240', // each max 10MB
        ];
    }
}
