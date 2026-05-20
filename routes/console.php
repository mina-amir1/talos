<?php

use App\Models\TalosSettings;
use App\Services\StorageSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

try {
    if (app(StorageSettings::class)->isBackupConfigured()) {
        $freq = TalosSettings::get('r2_backup_schedule', 'daily');
        $job  = Schedule::command('talos:backup');
        $freq === 'weekly' ? $job->weeklyOn(0, '02:00') : $job->dailyAt('02:00');
    }
} catch (\Throwable) {
    // DB not ready yet (fresh install) — skip scheduling
}
