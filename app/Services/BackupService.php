<?php

namespace App\Services;

use App\Models\TalosSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
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

    public function restore(string $key): void
    {
        $disk    = $this->storage->backupDisk();
        $tmpPath = sys_get_temp_dir() . '/talos-restore-' . basename($key);

        $stream = $disk->readStream($key);
        if (! $stream) {
            throw new \RuntimeException("Could not open backup file from R2. Check bucket permissions.");
        }

        try {
            $dest = fopen($tmpPath, 'wb');
            stream_copy_to_stream($stream, $dest);
            fclose($dest);
            fclose($stream);
        } catch (\Throwable $e) {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
            throw $e;
        }

        try {
            $this->restoreFromPath($tmpPath);
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }
    }

    public function restoreFromUpload(string $uploadedPath): void
    {
        $this->restoreFromPath($uploadedPath);
    }

    private function restoreFromPath(string $zipPath): void
    {
        $zip    = new ZipArchive();
        $result = $zip->open($zipPath);
        if ($result !== true) {
            throw new \RuntimeException("Cannot open zip archive (error code: {$result}).");
        }
        if ($zip->locateName('database/talos.sqlite') === false) {
            $zip->close();
            throw new \RuntimeException('Invalid backup: missing database/talos.sqlite.');
        }

        $extractDir = sys_get_temp_dir() . '/talos-restore-' . uniqid();
        mkdir($extractDir, 0755, true);

        try {
            if (! $zip->extractTo($extractDir)) {
                $zip->close();
                throw new \RuntimeException('Failed to extract backup archive.');
            }
            $zip->close();

            // Restore database
            $dbPath      = config('database.connections.sqlite.database');
            $extractedDb = $extractDir . '/database/talos.sqlite';
            if (! $dbPath) {
                throw new \RuntimeException('SQLite database path is not configured.');
            }
            if (! file_exists($extractedDb)) {
                throw new \RuntimeException('Extracted backup is missing database/talos.sqlite.');
            }
            if (file_exists($dbPath)) {
                copy($dbPath, $dbPath . '.pre-restore');
            }
            if (! copy($extractedDb, $dbPath)) {
                throw new \RuntimeException("Failed to write restored database to: {$dbPath}");
            }
            // Remove WAL sidecar files so SQLite doesn't replay current-session
            // transactions over the just-restored database on the next open.
            @unlink($dbPath . '-wal');
            @unlink($dbPath . '-shm');

            // Restore schemas
            $schemaBase    = config('talos.schema_path', base_path('talos'));
            $schemaExtract = $extractDir . '/schemas';
            if (is_dir($schemaExtract) && is_dir($schemaBase)) {
                File::cleanDirectory($schemaBase);
                File::copyDirectory($schemaExtract, $schemaBase);
            }

            // Restore local media — $relative already carries the full disk-relative path
            // (e.g. talos/media/foo.jpg) so no extra prefix is needed
            $mediaExtract = $extractDir . '/media';
            if (is_dir($mediaExtract) && ! $this->storage->isR2MediaEnabled()) {
                $localDisk = Storage::disk('public');
                foreach (File::allFiles($mediaExtract) as $file) {
                    $relative = ltrim(str_replace($mediaExtract, '', $file->getPathname()), '/\\');
                    $localDisk->put($relative, file_get_contents($file->getPathname()));
                }
            }

            Artisan::call('optimize');
        } finally {
            File::deleteDirectory($extractDir);
        }
    }
}
