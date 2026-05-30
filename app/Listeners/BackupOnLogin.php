<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BackupOnLogin
{
    public function handle(Login $event): void
    {
        $today     = Carbon::today()->format('Y-m-d');
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $todayFiles = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_' . $today . '*.sql');

        if (empty($todayFiles)) {
            Artisan::call('backup:database');
        }
    }
}
