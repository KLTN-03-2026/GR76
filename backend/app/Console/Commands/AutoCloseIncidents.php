<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SuCo;
use App\Models\NhatKyHeThong;
use App\Events\IncidentStatusChanged;
use Carbon\Carbon;

class AutoCloseIncidents extends Command
{
    protected $signature   = 'incidents:auto-close';
    protected $description = 'Tự động đóng các sự cố sau 12 giờ và broadcast real-time';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subHours(12);

        $incidents = SuCo::whereIn('trang_thai', ['pending', 'in_progress'])
            ->where('thoi_gian_dang', '<=', $cutoff)
            ->get();

        if ($incidents->isEmpty()) {
            $this->info('[AutoClose] Không có sự cố nào cần đóng.');
            return Command::SUCCESS;
        }

        foreach ($incidents as $suCo) {
            $suCo->update(['trang_thai' => 'resolved']);

            // Ghi nhật ký hệ thống
            NhatKyHeThong::create([
                'id_admin'  => null,
                'id_su_co'  => $suCo->id_su_co,
                'hanh_dong' => 'Tự động đóng sự cố',
                'ghi_chu'   => 'Sự cố tự động đóng sau 12 giờ không được xử lý',
            ]);

            // Broadcast real-time status change
            try {
                event(new IncidentStatusChanged($suCo->id_su_co, 'resolved', 'auto'));
            } catch (\Throwable $e) {
                $this->warn('Broadcast failed for incident ' . $suCo->id_su_co . ': ' . $e->getMessage());
            }
        }

        $this->info("[AutoClose] Đã đóng {$incidents->count()} sự cố sau 12 giờ.");
        return Command::SUCCESS;
    }
}
