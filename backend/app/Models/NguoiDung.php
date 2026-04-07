<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class NguoiDung extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'nguoi_dungs';
    protected $primaryKey = 'id_nguoi_dung';

    protected $fillable = [
        'ten',
        'email',
        'so_dien_thoai',
        'mat_khau',
        'id_vai_tro',
        'trang_thai',
        'ngay_tao',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'id_vai_tro', 'id_vai_tro');
    }

    public function thongBaos()
    {
        return $this->hasMany(ThongBao::class, 'id_nguoi_dung', 'id_nguoi_dung');
    }
}
