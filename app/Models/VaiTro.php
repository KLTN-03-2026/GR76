<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaiTro extends Model
{
    use HasFactory;

    protected $table = 'vai_tros';
    protected $primaryKey = 'id_vai_tro';

    protected $fillable = [
        'ten_vai_tro',
    ];

    public function nguoiDungs()
    {
        return $this->hasMany(NguoiDung::class, 'id_vai_tro', 'id_vai_tro');
    }
}
