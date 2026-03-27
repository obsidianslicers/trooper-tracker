<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDisplayController;
use App\Http\Controllers\Admin\SiteSettingsController;
use Illuminate\Support\Facades\Route;


//  ADMIN
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', AdminDisplayController::class)->name('display');

        Route::get('/settings', [SiteSettingsController::class, 'show'])->name('settings');
        Route::post('/settings', [SiteSettingsController::class, 'update']);
    });
