<?php

namespace App\Services;

use App\Models\SuCo;

class DashboardService
{
    public function getStats(): array
    {
        $total = SuCo::count();

        // Get counts grouped by exact trang_thai value stored in DB
        $byStatus = SuCo::selectRaw('trang_thai, COUNT(*) as jumlah')
            ->groupBy('trang_thai')
            ->pluck('jumlah', 'trang_thai');

        $byCategory = SuCo::selectRaw('id_loai_su_co, COUNT(*) as so_luong')
            ->groupBy('id_loai_su_co')
            ->with('loaiSuCo:id_loai_su_co,ten_loai')
            ->get()
            ->map(fn ($row) => [
                'ten_loai' => $row->loaiSuCo?->ten_loai ?? 'Không rõ',
                'so_luong' => $row->so_luong,
            ]);

        $latest = SuCo::with([
                'nguoiDung:id_nguoi_dung,ten,email',
                'loaiSuCo:id_loai_su_co,ten_loai',
                'mucDoKhanCap:id_muc_do,ten_muc_do'
            ])
            ->orderBy('thoi_gian_dang', 'desc')
            ->limit(10)
            ->get();

        return [
            'tong_su_co'        => $total,
            'moi_tiep_nhan'     => $byStatus->get('Mới tiếp nhận', 0),
            'dang_xu_ly'        => $byStatus->get('Đang xử lý',    0),
            'da_xac_thuc'       => $byStatus->get('Đã xác thực',   0),
            'tu_choi'           => $byStatus->get('Từ chối',        0),
            'theo_loai_su_co'   => $byCategory,
            'theo_trang_thai'   => $byStatus,
            'moi_nhat'          => $latest,
        ];
    }
}
