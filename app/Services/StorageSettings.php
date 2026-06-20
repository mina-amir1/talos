<?php

namespace App\Services;

use App\Models\TalosSettings;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class StorageSettings
{
    public function isR2MediaEnabled(): bool
    {
        return TalosSettings::get('r2_media_enabled') === '1'
            && $this->isR2MediaConfigured();
    }

    public function isR2MediaConfigured(): bool
    {
        return TalosSettings::get('r2_media_account_id')
            && TalosSettings::get('r2_media_access_key')
            && TalosSettings::get('r2_media_secret_key')
            && TalosSettings::get('r2_media_bucket');
    }

    public function isBackupConfigured(): bool
    {
        return TalosSettings::get('r2_backup_account_id')
            && TalosSettings::get('r2_backup_access_key')
            && TalosSettings::get('r2_backup_secret_key')
            && TalosSettings::get('r2_backup_bucket');
    }

    public function mediaDisk(): Filesystem
    {
        if ($this->isR2MediaEnabled()) {
            return $this->buildR2Disk(
                TalosSettings::get('r2_media_account_id'),
                TalosSettings::get('r2_media_access_key'),
                TalosSettings::get('r2_media_secret_key'),
                TalosSettings::get('r2_media_bucket'),
            );
        }

        return Storage::disk('public');
    }

    public function localDisk(): Filesystem
    {
        return Storage::disk('public');
    }

    public function backupDisk(): Filesystem
    {
        if (! $this->isBackupConfigured()) {
            throw new \RuntimeException('Backup R2 is not configured.');
        }

        return $this->buildR2Disk(
            TalosSettings::get('r2_backup_account_id'),
            TalosSettings::get('r2_backup_access_key'),
            TalosSettings::get('r2_backup_secret_key'),
            TalosSettings::get('r2_backup_bucket'),
        );
    }

    public function mediaUrl(string $path): string
    {
        if ($this->isR2MediaEnabled()) {
            $domain = rtrim(TalosSettings::get('r2_media_domain', ''), '/');
            if ($domain) {
                return $domain . '/' . ltrim($path, '/');
            }
            // Fall back to R2 public bucket URL
            $accountId = TalosSettings::get('r2_media_account_id');
            $bucket    = TalosSettings::get('r2_media_bucket');
            return "https://{$accountId}.r2.cloudflarestorage.com/{$bucket}/" . ltrim($path, '/');
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public function testMediaConnection(): bool
    {
        return $this->testMediaConnectionWith(
            TalosSettings::get('r2_media_account_id'),
            TalosSettings::get('r2_media_access_key'),
            TalosSettings::get('r2_media_secret_key'),
            TalosSettings::get('r2_media_bucket'),
        );
    }

    public function testMediaConnectionWith(string $accountId, string $accessKey, string $secretKey, string $bucket): bool
    {
        $disk = $this->buildR2Disk($accountId, $accessKey, $secretKey, $bucket);
        $disk->put('.talos-probe', 'ok');
        $disk->delete('.talos-probe');
        return true;
    }

    public function testBackupConnection(): bool
    {
        return $this->testBackupConnectionWith(
            TalosSettings::get('r2_backup_account_id'),
            TalosSettings::get('r2_backup_access_key'),
            TalosSettings::get('r2_backup_secret_key'),
            TalosSettings::get('r2_backup_bucket'),
        );
    }

    public function testBackupConnectionWith(string $accountId, string $accessKey, string $secretKey, string $bucket): bool
    {
        $disk = $this->buildR2Disk($accountId, $accessKey, $secretKey, $bucket);
        $disk->put('.talos-probe', 'ok');
        $disk->delete('.talos-probe');
        return true;
    }

    private function buildR2Disk(string $accountId, string $accessKey, string $secretKey, string $bucket): Filesystem
    {
        return Storage::build([
            'driver'                  => 's3',
            'key'                     => $accessKey,
            'secret'                  => $secretKey,
            'region'                  => 'auto',
            'bucket'                  => $bucket,
            'endpoint'                => "https://{$accountId}.r2.cloudflarestorage.com",
            'use_path_style_endpoint' => false,
            'throw'                   => true,
        ]);
    }
}
