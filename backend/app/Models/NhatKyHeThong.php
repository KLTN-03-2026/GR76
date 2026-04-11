<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhatKyHeThong extends Model
{
    use HasFactory;

    protected $table = 'nhat_ky_he_thong';
    protected $primaryKey = 'id_log';

    protected $fillable = [
        'id_admin',
        'id_su_co',
        'hanh_dong',
        'ghi_chu',
        'thoi_gian',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }

    public function suCo()
    {
        return $this->belongsTo(SuCo::class, 'id_su_co', 'id_su_co');
    }
}
