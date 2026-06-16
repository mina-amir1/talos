<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\ContentManagerController;
use App\Http\Controllers\Admin\ContentTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StorageSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('talos.dashboard'));

$prefix = config('talos.admin_prefix', 'talos');

// ── Auth ────────────────────────────────────────────────────────────────────
Route::prefix($prefix)->name('talos.')->group(function () {

    Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // ── Protected admin routes ─────────────────────────────────────────────
    Route::middleware(\App\Http\Middleware\TalosAdminAuth::class)->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Content-Type Builder
        Route::prefix('content-type-builder')->name('content-type-builder.')->group(function () {
            Route::get('/',         [ContentTypeController::class, 'index'])->name('index');
            Route::get('/create',   [ContentTypeController::class, 'create'])->name('create');
            Route::post('/',        [ContentTypeController::class, 'store'])->name('store');
            Route::get('/{uid}',    [ContentTypeController::class, 'edit'])->name('edit');
            Route::put('/{uid}',    [ContentTypeController::class, 'update'])->name('update');
            Route::delete('/{uid}', [ContentTypeController::class, 'destroy'])->name('destroy');
        });

        // Components
        Route::prefix('components')->name('components.')->group(function () {
            Route::get('/',         [ComponentController::class, 'index'])->name('index');
            Route::get('/create',   [ComponentController::class, 'create'])->name('create');
            Route::post('/',        [ComponentController::class, 'store'])->name('store');
            Route::get('/{uid}',    [ComponentController::class, 'edit'])->name('edit');
            Route::put('/{uid}',    [ComponentController::class, 'update'])->name('update');
            Route::delete('/{uid}', [ComponentController::class, 'destroy'])->name('destroy');
        });

        // Content Manager
        Route::prefix('content-manager')->name('content.')->group(function () {
            Route::get('/{uid}',                 [ContentManagerController::class, 'index'])->name('index');
            Route::get('/{uid}/create',          [ContentManagerController::class, 'create'])->name('create');
            Route::post('/{uid}',                [ContentManagerController::class, 'store'])->name('store');
            Route::get('/{uid}/{id}',            [ContentManagerController::class, 'edit'])->name('edit');
            Route::put('/{uid}/{id}',            [ContentManagerController::class, 'update'])->name('update');
            Route::delete('/{uid}/{id}',         [ContentManagerController::class, 'destroy'])->name('destroy');
            Route::post('/{uid}/{id}/publish',   [ContentManagerController::class, 'publish'])->name('publish');
            Route::post('/{uid}/{id}/unpublish', [ContentManagerController::class, 'unpublish'])->name('unpublish');
            Route::post('/{uid}/{id}/translate', [ContentManagerController::class, 'translate'])->name('translate');
            Route::post('/{uid}/field-order',    [ContentManagerController::class, 'saveFieldOrder'])->name('field-order');
        });

        // Media Library
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/',               [MediaController::class, 'index'])->name('index');
            Route::post('/upload',        [MediaController::class, 'upload'])->name('upload');
            Route::post('/folders',       [MediaController::class, 'storeFolder'])->name('folders.store');
            Route::delete('/folders',     [MediaController::class, 'destroyFolder'])->name('folders.destroy');
            Route::put('/{id}/move',      [MediaController::class, 'moveFile'])->name('move');
            Route::get('/items',          [MediaController::class, 'items'])->name('items');
            Route::get('/{id}',           [MediaController::class, 'show'])->name('show');
            Route::delete('/{id}',        [MediaController::class, 'destroy'])->name('destroy');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('locales',            [SettingsController::class, 'locales'])->name('locales');
            Route::post('locales',           [SettingsController::class, 'storeLocale'])->name('locales.store');
            Route::delete('locales/{code}',  [SettingsController::class, 'destroyLocale'])->name('locales.destroy');

            Route::get('roles',              [SettingsController::class, 'roles'])->name('roles');
            Route::post('roles',             [SettingsController::class, 'storeRole'])->name('roles.store');
            Route::put('roles/{id}',         [SettingsController::class, 'updateRole'])->name('roles.update');
            Route::delete('roles/{id}',      [SettingsController::class, 'destroyRole'])->name('roles.destroy');

            Route::get('users',                    [SettingsController::class, 'users'])->name('users');
            Route::post('users',                   [SettingsController::class, 'storeUser'])->name('users.store');
            Route::delete('users/{id}',            [SettingsController::class, 'destroyUser'])->name('users.destroy');
            Route::put('users/{id}/password',      [SettingsController::class, 'resetUserPassword'])->name('users.reset-password');

            Route::get('profile',                  [SettingsController::class, 'profile'])->name('profile');
            Route::put('profile/password',         [SettingsController::class, 'changeOwnPassword'])->name('profile.password');

            Route::get('api-tokens',         [SettingsController::class, 'apiTokens'])->name('api-tokens');
            Route::post('api-tokens',        [SettingsController::class, 'storeApiToken'])->name('api-tokens.store');
            Route::delete('api-tokens/{id}', [SettingsController::class, 'destroyApiToken'])->name('api-tokens.destroy');

            // Storage (super admin only — enforced in controller)
            Route::get('storage',                     [StorageSettingsController::class, 'storage'])->name('storage');
            Route::post('storage',                    [StorageSettingsController::class, 'saveStorage'])->name('storage.save');
            Route::post('storage/toggle',             [StorageSettingsController::class, 'toggleStorage'])->name('storage.toggle');
            Route::post('storage/test',               [StorageSettingsController::class, 'testStorage'])->name('storage.test');
            Route::post('storage/migrate',            [StorageSettingsController::class, 'startMigration'])->name('storage.migrate');
            Route::get('storage/migration-status',    [StorageSettingsController::class, 'migrationStatus'])->name('storage.migration-status');

            // Backup (super admin only — enforced in controller)
            Route::get('backup',                      [StorageSettingsController::class, 'backup'])->name('backup');
            Route::post('backup',                     [StorageSettingsController::class, 'saveBackup'])->name('backup.save');
            Route::post('backup/test',                [StorageSettingsController::class, 'testBackup'])->name('backup.test');
            Route::post('backup/trigger',             [StorageSettingsController::class, 'triggerBackup'])->name('backup.trigger');
            Route::delete('backup',                   [StorageSettingsController::class, 'deleteBackup'])->name('backup.delete');
        });
    });
});
