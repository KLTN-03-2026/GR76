<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động giải quyết sự cố cũ hơn 18h, chạy mỗi giờ
Schedule::command('suCo:auto-resolve')->hourly();
