<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admins';
    protected $primaryKey = 'id_admin';

    protected $fillable = [
        'ten_dang_nhap',
        'mat_khau',
        'ho_ten',
        'email',
        'so_dien_thoai',
        'ngay_tao',
        'avatar',
    ];

    protected $appends = ['avatar_url'];

    protected $hidden = [
        'mat_khau',
    ];

   
    public function getAuthPassword()
    {
        return $this->mat_khau;
    }

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) return null;
        if (str_starts_with($this->avatar, 'http')) return $this->avatar;
        return asset('storage/' . ltrim($this->avatar, '/'));
    }
}
