<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\MigrateMediaToR2;
use App\Models\TalosSettings;
use App\Services\BackupService;
use App\Services\StorageSettings;
use Illuminate\Http\Request;

class StorageSettingsController extends Controller
{
    public function __construct(
        private StorageSettings $storage,
        private BackupService   $backup,
    ) {}

    // ── Storage ───────────────────────────────────────────────────────────────

    public function storage()
    {
        $config = [
            'r2_media_enabled'      => TalosSettings::get('r2_media_enabled', '0'),
            'r2_media_account_id'   => TalosSettings::get('r2_media_account_id', ''),
            'r2_media_access_key'   => TalosSettings::get('r2_media_access_key', ''),
            'r2_media_bucket'       => TalosSettings::get('r2_media_bucket', ''),
            'r2_media_domain'       => TalosSettings::get('r2_media_domain', ''),
            'r2_migration_status'   => TalosSettings::get('r2_migration_status', 'idle'),
            'r2_migration_progress' => TalosSettings::get('r2_migration_progress', '0/0'),
            'r2_migration_failed'   => TalosSettings::get('r2_migration_failed', '0'),
        ];

        return view('talos.settings.storage', compact('config'));
    }

    public function saveStorage(Request $request)
    {
        $request->validate([
            'r2_media_account_id' => 'required|string',
            'r2_media_access_key' => 'required|string',
            'r2_media_bucket'     => 'required|string',
            'r2_media_domain'     => 'nullable|url',
        ]);

        TalosSettings::setBulk([
            'r2_media_account_id' => $request->r2_media_account_id,
            'r2_media_access_key' => $request->r2_media_access_key,
            'r2_media_bucket'     => $request->r2_media_bucket,
            'r2_media_domain'     => $request->r2_media_domain ?? '',
        ]);

        if ($request->filled('r2_media_secret_key')) {
            TalosSettings::set('r2_media_secret_key', $request->r2_media_secret_key);
        }

        return back()->with('success', 'Media storage settings saved.');
    }

    public function toggleStorage(Request $request)
    {
        $enable = $request->boolean('enabled');

        if ($enable && ! $this->storage->isR2MediaConfigured()) {
            return response()->json(['error' => 'Save R2 credentials first.'], 422);
        }

        TalosSettings::set('r2_media_enabled', $enable ? '1' : '0');

        return response()->json(['enabled' => $enable]);
    }

    public function testStorage(Request $request)
    {
        try {
            $ok = $this->storage->testMediaConnectionWith(
                $request->input('r2_media_account_id') ?: TalosSettings::get('r2_media_account_id'),
                $request->input('r2_media_access_key') ?: TalosSettings::get('r2_media_access_key'),
                $request->input('r2_media_secret_key') ?: TalosSettings::get('r2_media_secret_key'),
                $request->input('r2_media_bucket')     ?: TalosSettings::get('r2_media_bucket'),
            );

            return response()->json(['ok' => $ok]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $this->storage->friendlyConnectionError($e)]);
        }
    }

    public function startMigration()
    {
        if (! $this->storage->isR2MediaConfigured()) {
            return response()->json(['error' => 'Configure R2 credentials first.'], 422);
        }

        if (TalosSettings::get('r2_migration_status') === 'running') {
            return response()->json(['error' => 'Migration already running.'], 422);
        }

        TalosSettings::set('r2_migration_status', 'pending');
        TalosSettings::set('r2_migration_progress', '0/0');
        MigrateMediaToR2::dispatch();

        return response()->json(['started' => true]);
    }

    public function migrationStatus()
    {
        return response()->json([
            'status'   => TalosSettings::get('r2_migration_status', 'idle'),
            'progress' => TalosSettings::get('r2_migration_progress', '0/0'),
            'failed'   => TalosSettings::get('r2_migration_failed', '0'),
        ]);
    }

    // ── Backup ────────────────────────────────────────────────────────────────

    public function backup()
    {
        $config = [
            'r2_backup_account_id' => TalosSettings::get('r2_backup_account_id', ''),
            'r2_backup_access_key' => TalosSettings::get('r2_backup_access_key', ''),
            'r2_backup_bucket'     => TalosSettings::get('r2_backup_bucket', ''),
            'r2_backup_schedule'   => TalosSettings::get('r2_backup_schedule', 'daily'),
            'r2_backup_retention'  => TalosSettings::get('r2_backup_retention', '30'),
            'r2_backup_last_run'   => TalosSettings::get('r2_backup_last_run', ''),
        ];

        $history = $this->backup->list();

        return view('talos.settings.backup', compact('config', 'history'));
    }

    public function saveBackup(Request $request)
    {
        $request->validate([
            'r2_backup_account_id' => 'required|string',
            'r2_backup_access_key' => 'required|string',
            'r2_backup_bucket'     => 'required|string',
            'r2_backup_schedule'   => 'required|in:daily,weekly',
            'r2_backup_retention'  => 'required|integer|min:1|max:365',
        ]);

        TalosSettings::setBulk([
            'r2_backup_account_id' => $request->r2_backup_account_id,
            'r2_backup_access_key' => $request->r2_backup_access_key,
            'r2_backup_bucket'     => $request->r2_backup_bucket,
            'r2_backup_schedule'   => $request->r2_backup_schedule,
            'r2_backup_retention'  => $request->r2_backup_retention,
        ]);

        if ($request->filled('r2_backup_secret_key')) {
            TalosSettings::set('r2_backup_secret_key', $request->r2_backup_secret_key);
        }

        return back()->with('success', 'Backup settings saved.');
    }

    public function testBackup(Request $request)
    {
        $accountId = $request->input('r2_backup_account_id') ?: TalosSettings::get('r2_backup_account_id', '');
        $accessKey = $request->input('r2_backup_access_key') ?: TalosSettings::get('r2_backup_access_key', '');
        $secretKey = $request->input('r2_backup_secret_key') ?: TalosSettings::get('r2_backup_secret_key', '');
        $bucket    = $request->input('r2_backup_bucket')     ?: TalosSettings::get('r2_backup_bucket', '');

        if (! $accountId || ! $accessKey || ! $secretKey || ! $bucket) {
            return response()->json(['ok' => false, 'error' => 'All credentials are required. Enter the secret key to test.']);
        }

        try {
            $disk     = $this->storage->makeDiskWith($accountId, $accessKey, $secretKey, $bucket);
            $disk->put('.talos-probe', 'ok');
            $disk->delete('.talos-probe');
            $existing = count($disk->files('backups'));

            return response()->json(['ok' => true, 'existing' => $existing]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $this->storage->friendlyConnectionError($e)]);
        }
    }

    public function restoreBackup(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        try {
            $this->backup->restore($request->input('key'));
            return response()->json(['restored' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function uploadRestoreBackup(Request $request)
    {
        $request->validate(['file' => 'required|file|extensions:zip']);

        try {
            $this->backup->restoreFromUpload($request->file('file')->getRealPath());
            return response()->json(['restored' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function triggerBackup()
    {
        try {
            $key = $this->backup->run();
            return response()->json(['key' => $key]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function downloadBackup()
    {
        ['path' => $path, 'name' => $name] = $this->backup->createZip();

        return response()->download($path, $name)->deleteFileAfterSend(true);
    }

    public function deleteBackup(Request $request)
    {
        $request->validate(['key' => 'required|string']);
        $this->backup->delete($request->input('key'));

        return response()->json(['deleted' => true]);
    }
}
