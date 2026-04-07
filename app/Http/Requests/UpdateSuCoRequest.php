<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuCoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'tieu_de' => 'sometimes|required|string|min:5|max:200',
            'noi_dung' => 'sometimes|required|string|min:10',
            'dia_chi' => 'sometimes|required|string|min:5|max:255',
            'vi_do' => 'sometimes|required|numeric',
            'kinh_do' => 'sometimes|required|numeric',
            'id_loai_su_co' => 'sometimes|required|exists:loai_su_cos,id_loai_su_co',
            'id_muc_do' => 'sometimes|required|exists:muc_do_khan_caps,id_muc_do',
            'hinh_anh' => 'nullable|string',
        ];
    }
}
