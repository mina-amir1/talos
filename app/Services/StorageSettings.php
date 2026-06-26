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

    public function makeDiskWith(string $accountId, string $accessKey, string $secretKey, string $bucket): Filesystem
    {
        return $this->buildR2Disk($accountId, $accessKey, $secretKey, $bucket);
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

    public function friendlyConnectionError(\Throwable $e): string
    {
        $msg = $e->getMessage();

        return match (true) {
            str_contains($msg, 'Could not resolve host'),
            str_contains($msg, 'cURL error 6')    => 'Could not reach the storage server. Your Account ID is likely incorrect.',

            str_contains($msg, 'TLS'),
            str_contains($msg, 'SSL'),
            str_contains($msg, 'handshake'),
            str_contains($msg, 'cURL error 35')   => 'Secure connection failed. Your Account ID is likely incorrect.',

            str_contains($msg, 'timed out'),
            str_contains($msg, 'cURL error 28')   => 'Connection timed out. Check your Account ID and try again.',

            str_contains($msg, 'InvalidAccessKeyId')   => 'Access Key ID not recognised. Double-check your Access Key ID.',
            str_contains($msg, 'SignatureDoesNotMatch') => 'Secret Access Key is incorrect. Please re-enter it.',
            str_contains($msg, 'NoSuchBucket')         => 'Bucket not found. Make sure the bucket name is correct and the bucket exists.',

            str_contains($msg, 'AccessDenied'),
            str_contains($msg, '403 Forbidden')   => 'Access denied. Your credentials do not have permission to access this bucket.',

            str_contains($msg, 'NoSuchKey'),
            str_contains($msg, '404')             => 'Bucket or resource not found. Check the bucket name.',

            default => 'Could not connect. Please check all credentials and try again.',
        };
    }
}
