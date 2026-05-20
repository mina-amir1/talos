<?php

namespace App\Services;

use App\Models\TalosSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupService
{
    public function __construct(private StorageSettings $storage) {}

    public function run(): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $zipName   = "talos-backup-{$timestamp}.zip";
        $tmpPath   = sys_get_temp_dir() . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create zip at {$tmpPath}");
        }

        // Database
        $dbPath = config('database.connections.sqlite.database');
        if ($dbPath && file_exists($dbPath)) {
            $zip->addFile($dbPath, 'database/talos.sqlite');
        }

        // Schema JSON files (content types + components)
        $schemaBase = config('talos.schema_path', base_path('talos'));
        if (is_dir($schemaBase)) {
            $files = File::allFiles($schemaBase);
            foreach ($files as $file) {
                $relative = ltrim(str_replace($schemaBase, '', $file->getPathname()), '/\\');
                $zip->addFile($file->getPathname(), 'schemas/' . $relative);
            }
        }

        $zip->close();

        // Upload to backup R2
        $disk    = $this->storage->backupDisk();
        $r2Key   = 'backups/' . $zipName;
        $disk->put($r2Key, file_get_contents($tmpPath));

        unlink($tmpPath);

        TalosSettings::set('r2_backup_last_run', Carbon::now()->toIso8601String());

        return $r2Key;
    }

    public function list(): array
    {
        if (! $this->storage->isBackupConfigured()) {
            return [];
        }

        try {
            $disk  = $this->storage->backupDisk();
            $files = $disk->files('backups');

            return collect($files)
                ->map(fn($f) => [
                    'key'      => $f,
                    'name'     => basename($f),
                    'size'     => $disk->size($f),
                    'modified' => Carbon::createFromTimestamp($disk->lastModified($f))->toDateTimeString(),
                ])
                ->sortByDesc('modified')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function delete(string $key): void
    {
        $this->storage->backupDisk()->delete($key);
    }
}
