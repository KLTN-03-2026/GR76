<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuCo extends Model
{
    use HasFactory;

    protected $table = 'su_cos';
    protected $primaryKey = 'id_su_co';

    protected $fillable = [
        'id_nguoi_dung',
        'tieu_de',
        'noi_dung',
        'hinh_anh',
        'dia_chi',
        'vi_do',
        'kinh_do',
        'id_loai_su_co',
        'id_muc_do',
        'trang_thai',
        'thoi_gian_dang',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'id_nguoi_dung', 'id_nguoi_dung');
    }

    public function loaiSuCo()
    {
        return $this->belongsTo(LoaiSuCo::class, 'id_loai_su_co', 'id_loai_su_co');
    }

    public function mucDoKhanCap()
    {
        return $this->belongsTo(MucDoKhanCap::class, 'id_muc_do', 'id_muc_do');
    }

    public function phanTichAi()
    {
        return $this->hasOne(PhanTichAi::class, 'id_su_co', 'id_su_co');
    }
}
