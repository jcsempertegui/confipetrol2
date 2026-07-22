<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Backup automático diario a la 1:00 AM
Schedule::command('backup:database --type=automatico')
    ->dailyAt('01:00')
    ->withoutOverlapping(180);
