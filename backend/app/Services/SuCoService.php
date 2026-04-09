<?php

namespace App\Services;

use App\Events\NewIncidentCreated;
use App\Models\SuCo;
use App\Models\NhatKyHeThong;

class SuCoService
{
    public function createIncident(array $data, int $userId): SuCo
    {
        $data['id_nguoi_dung'] = $userId;
        $data['trang_thai'] = 'pending';
        $suCo = SuCo::create($data);

        // Load relations so broadcast data is complete
        $suCo->load(['loaiSuCo', 'mucDoKhanCap', 'nguoiDung']);

        event(new NewIncidentCreated($suCo));

        return $suCo;
    }

    public function getMapIncidents()
    {
        return SuCo::whereNotNull('vi_do')
            ->whereNotNull('kinh_do')
            ->whereNotIn('trang_thai', ['resolved', 'rejected'])
            ->with(['mucDoKhanCap', 'loaiSuCo'])
            ->orderBy('thoi_gian_dang', 'desc')
            ->get();
    }

    public function updateIncident(SuCo $suCo, array $data)
    {
        $suCo->update($data);
        return $suCo;
    }

    public function updateStatus(SuCo $suCo, string $status, int $adminId = null)
    {
        $suCo->update(['trang_thai' => $status]);
        
        if ($adminId) {
            NhatKyHeThong::create([
                'id_admin' => $adminId,
                'id_su_co' => $suCo->id_su_co,
                'hanh_dong' => 'Cập nhật trạng thái sự cố',
                'ghi_chu' => 'Chuyển sang: ' . $status,
            ]);
        }

        return $suCo;
    }
}
