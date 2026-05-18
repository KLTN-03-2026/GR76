<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'hinh_anhs',
        'dia_chi',
        'vi_do',
        'kinh_do',
        'id_loai_su_co',
        'id_muc_do',
        'trang_thai',
        'thoi_gian_dang',
    ];

    protected $casts = [
        'hinh_anhs' => 'array',
    ];

    protected $appends = ['hinh_anh_url', 'hinh_anhs_urls'];

    /**
     * Accessor: return full absolute URL for hinh_anh.
     * Handles both '/storage/...' paths and full URLs.
     */
    public function getHinhAnhUrlAttribute(): ?string
    {
        if (!$this->hinh_anh) {
            return null;
        }
        // If already a full URL, return as-is
        if (str_starts_with($this->hinh_anh, 'http')) {
            return $this->hinh_anh;
        }
        // Return root-relative path (works with Vite proxy in dev & nginx in prod)
        $path = ltrim($this->hinh_anh, '/');
        // If path already has storage/ prefix, use as-is; otherwise add it
        if (!str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }
        return '/' . $path;
    }

    /**
     * Accessor: return array of URLs for hinh_anhs (multiple images).
     */
    public function getHinhAnhsUrlsAttribute(): array
    {
        $images = $this->hinh_anhs ?? [];
        return array_map(function ($path) {
            if (!$path) return null;
            if (str_starts_with($path, 'http')) return $path;
            $p = ltrim($path, '/');
            if (!str_starts_with($p, 'storage/')) {
                $p = 'storage/' . $p;
            }
            return '/' . $p;
        }, $images);
    }

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
