<?php

use App\Http\Controllers\ApiSettingsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\RankingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth routes (public)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // ── Dashboard (portfolio) ────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Websites ─────────────────────────────────────────────────
    Route::get('/websites/{website}', [WebsiteController::class, 'show'])->name('websites.show');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/websites',            [WebsiteController::class, 'store'])->name('websites.store');
        Route::put('/websites/{website}',   [WebsiteController::class, 'update'])->name('websites.update');
    });

    // ── Keywords (viewer: read | manager+: write | admin: delete) ─
    Route::get('/keywords', [KeywordController::class, 'index'])->name('keywords.index');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/keywords',           [KeywordController::class, 'store'])->name('keywords.store');
        Route::put('/keywords/{keyword}',  [KeywordController::class, 'update'])->name('keywords.update');
    });

    Route::delete('/keywords/{keyword}', [KeywordController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('keywords.destroy');

    // ── Rankings ─────────────────────────────────────────────────
    Route::post('/rankings/refresh', [RankingsController::class, 'refresh'])
        ->middleware('role:admin,manager')
        ->name('rankings.refresh');

    Route::get('/rankings/export', [RankingsController::class, 'export'])
        ->name('rankings.export');

    // ── Admin-only routes ────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {

        // Audit log
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        // Job logs viewer
        Route::get('/logs', [LogViewerController::class, 'index'])->name('logs.index');

        // User management
        Route::get('/users',              [UserController::class, 'index'])->name('users.index');
        Route::post('/users',             [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}',       [UserController::class, 'update'])->name('users.update');

        // API settings
        Route::get('/settings/api',       [ApiSettingsController::class, 'index'])->name('settings.api.index');
        Route::put('/settings/api',       [ApiSettingsController::class, 'update'])->name('settings.api.update');
        Route::get('/settings/api/test',  [ApiSettingsController::class, 'test'])->name('settings.api.test');
    });

    // ── Profile ──────────────────────────────────────────────────
    Route::get('/profile', fn() => view('profile'))->name('profile');
});
