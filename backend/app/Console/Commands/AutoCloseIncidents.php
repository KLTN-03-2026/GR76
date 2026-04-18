<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SuCo;
use App\Models\NhatKyHeThong;
use Carbon\Carbon;

class AutoCloseIncidents extends Command
{
    protected $signature   = 'incidents:auto-close';
    protected $description = 'Tự động đóng các sự cố chưa được giải quyết sau 24 giờ';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subHours(24);

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
                'ghi_chu'   => 'Sự cố tự động đóng sau 24 giờ không được xử lý',
            ]);
        }

        $this->info("[AutoClose] Đã đóng {$incidents->count()} sự cố.");
        return Command::SUCCESS;
    }
}
