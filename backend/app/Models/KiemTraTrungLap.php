<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KiemTraTrungLap extends Model
{
    use HasFactory;

    protected $table = 'kiem_tra_trung_lap';
    protected $primaryKey = 'id_trung_lap';

    protected $fillable = [
        'id_su_co_1',
        'id_su_co_2',
        'do_tuong_dong',
        'ket_qua',
        'thoi_gian_kiem_tra',
    ];

    public function suCo1()
    {
        return $this->belongsTo(SuCo::class, 'id_su_co_1', 'id_su_co');
    }

    public function suCo2()
    {
        return $this->belongsTo(SuCo::class, 'id_su_co_2', 'id_su_co');
    }
}
