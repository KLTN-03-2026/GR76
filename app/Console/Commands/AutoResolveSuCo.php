<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SuCo;
use Carbon\Carbon;

class AutoResolveSuCo extends Command
{
    protected $signature   = 'suCo:auto-resolve';
    protected $description = 'Tự động chuyển sự cố cũ hơn 18 giờ sang trạng thái Đã xác thực';

    public function handle(): void
    {
        $cutoff   = Carbon::now()->subHours(18);
        $resolved = 'Đã xác thực';

        $count = SuCo::whereNotIn('trang_thai', [$resolved, 'Từ chối'])
            ->where(function ($q) use ($cutoff) {
                $q->where('thoi_gian_dang', '<=', $cutoff)
                  ->orWhere('created_at',   '<=', $cutoff);
            })
            ->update(['trang_thai' => $resolved]);

        $this->info("✅ Đã cập nhật {$count} sự cố cũ hơn 18h sang '{$resolved}'.");
    }
}
