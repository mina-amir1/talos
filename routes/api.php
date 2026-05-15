<?php

use App\Http\Controllers\Api\ContentApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Talos Content API
|--------------------------------------------------------------------------
| Collection types use {pluralName}  (e.g. /api/articles, /api/articles/1)
| Single types     use {singularName} (e.g. /api/homepage — no ID needed)
|
| The controller resolves which kind a name belongs to and acts accordingly.
|
| Protected by the TalosApiAuth middleware (Bearer token).
*/

Route::middleware(\App\Http\Middleware\TalosApiAuth::class)
    ->prefix('/')
    ->group(function () {
        // ── Collection-type routes (with ID) ──────────────────────────────
        Route::get('{name}/{id}',    [ContentApiController::class, 'show']);
        Route::put('{name}/{id}',    [ContentApiController::class, 'update']);
        Route::patch('{name}/{id}',  [ContentApiController::class, 'update']);
        Route::delete('{name}/{id}', [ContentApiController::class, 'destroy']);

        // ── Shared root routes ────────────────────────────────────────────
        // GET  → list (collection) or single entry (single type)
        // POST → create (collection) or upsert (single type)
        Route::get('{name}',   [ContentApiController::class, 'index']);
        Route::post('{name}',  [ContentApiController::class, 'store']);

        // ── Single-type routes (no ID) ────────────────────────────────────
        Route::put('{name}',    [ContentApiController::class, 'updateSingle']);
        Route::patch('{name}',  [ContentApiController::class, 'updateSingle']);
        Route::delete('{name}', [ContentApiController::class, 'destroySingle']);
    });
