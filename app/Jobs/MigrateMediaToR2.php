<?php

namespace App\Jobs;

use App\Models\TalosMedia;
use App\Models\TalosSettings;
use App\Services\StorageSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class MigrateMediaToR2 implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function handle(StorageSettings $settings): void
    {
        TalosSettings::set('r2_migration_status', 'running');
        TalosSettings::set('r2_migration_progress', '0/0');

        $local    = Storage::disk('public');
        $r2       = $settings->mediaDisk();
        $media    = TalosMedia::all();
        $total    = $media->count();
        $done     = 0;
        $failed   = 0;

        TalosSettings::set('r2_migration_progress', "0/{$total}");

        foreach ($media as $file) {
            try {
                if (! $local->exists($file->path)) {
                    $failed++;
                    continue;
                }

                // Skip if already on R2
                if ($r2->exists($file->path)) {
                    $done++;
                    continue;
                }

                $contents = $local->get($file->path);
                $r2->put($file->path, $contents);
                $done++;
            } catch (\Throwable) {
                $failed++;
            }

            TalosSettings::set('r2_migration_progress', "{$done}/{$total}");
        }

        TalosSettings::set('r2_migration_status', 'done');
        TalosSettings::set('r2_migration_failed', (string) $failed);
        TalosSettings::set('r2_migration_progress', "{$done}/{$total}");
    }

    public function failed(\Throwable $e): void
    {
        TalosSettings::set('r2_migration_status', 'failed');
    }
}
