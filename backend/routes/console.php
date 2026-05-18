<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động resolve sự cố sau 12 giờ - chạy mỗi phút để gần real-time
Schedule::command('incidents:auto-close')->everyMinute();

