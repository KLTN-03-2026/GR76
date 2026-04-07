<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MucDoKhanCap extends Model
{
    use HasFactory;

    protected $table = 'muc_do_khan_caps';
    protected $primaryKey = 'id_muc_do';

    protected $fillable = [
        'ten_muc_do',
        'do_uu_tien',
    ];
}
