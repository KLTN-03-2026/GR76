<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhanTichAi extends Model
{
    use HasFactory;

    protected $table = 'phan_tich_ai';
    protected $primaryKey = 'id_ai';

    protected $fillable = [
        'id_su_co',
        'loai_su_co',
        'muc_do_khan_cap',
        'do_tin_cay',
        'thoi_gian_phan_tich',
    ];

    public function suCo()
    {
        return $this->belongsTo(SuCo::class, 'id_su_co', 'id_su_co');
    }
}
