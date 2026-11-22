<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WO preventif otomatis. Command keluar tanpa membuat apa pun bila toggle
// "Buat Work Order Otomatis" di Site Setting masih nonaktif (default).
Schedule::command('work-orders:generate-scheduled')
    ->dailyAt('00:05')
    ->withoutOverlapping();
