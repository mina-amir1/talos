<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature   = 'talos:backup';
    protected $description = 'Backup Talos CMS database and schemas to R2';

    public function handle(BackupService $backup): int
    {
        $this->info('Starting Talos backup...');

        try {
            $key = $backup->run();
            $this->info("Backup complete: {$key}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
