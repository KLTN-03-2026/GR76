<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    use HasFactory;

    protected $table = 'thong_baos';
    protected $primaryKey = 'id_thong_bao';

    protected $fillable = [
        'id_admin',
        'id_nguoi_dung',
        'id_su_co',
        'tieu_de',
        'noi_dung',
        'da_doc',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'id_nguoi_dung', 'id_nguoi_dung');
    }

    public function suCo()
    {
        return $this->belongsTo(SuCo::class, 'id_su_co', 'id_su_co');
    }
}
