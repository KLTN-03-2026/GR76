<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoaiSuCo extends Model
{
    use HasFactory;

    protected $table = 'loai_su_cos';
    protected $primaryKey = 'id_loai_su_co';

    protected $fillable = [
        'ten_loai',
    ];
}
