<?php

namespace App\Services;

use App\Models\TalosSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupService
{
    public function __construct(private StorageSettings $storage) {}

    public function createZip(): array
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
            foreach (File::allFiles($schemaBase) as $file) {
                $relative = ltrim(str_replace($schemaBase, '', $file->getPathname()), '/\\');
                $zip->addFile($file->getPathname(), 'schemas/' . $relative);
            }
        }

        // Local media files — only when media is stored locally (not on R2)
        if (! $this->storage->isR2MediaEnabled()) {
            $mediaDir  = config('talos.media_directory', 'talos/media');
            $localDisk = Storage::disk('public');
            foreach ($localDisk->allFiles($mediaDir) as $file) {
                $abs = $localDisk->path($file);
                if (file_exists($abs)) {
                    $zip->addFile($abs, 'media/' . $file);
                }
            }
        }

        $zip->close();

        return ['path' => $tmpPath, 'name' => $zipName];
    }

    public function run(): string
    {
        ['path' => $tmpPath, 'name' => $zipName] = $this->createZip();

        try {
            $disk  = $this->storage->backupDisk();
            $r2Key = 'backups/' . $zipName;
            $disk->put($r2Key, file_get_contents($tmpPath));
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        TalosSettings::set('r2_backup_last_run', Carbon::now()->toIso8601String());

        $this->prune();

        return $r2Key;
    }

    public function prune(): void
    {
        if (! $this->storage->isBackupConfigured()) {
            return;
        }

        $retentionDays = (int) TalosSettings::get('r2_backup_retention', '30');
        $cutoff        = Carbon::now()->subDays($retentionDays);
        $disk          = $this->storage->backupDisk();

        foreach ($disk->files('backups') as $file) {
            if (Carbon::createFromTimestamp($disk->lastModified($file))->lt($cutoff)) {
                $disk->delete($file);
            }
        }
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
