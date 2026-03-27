<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDisplayController;
use App\Http\Controllers\Admin\SiteSettings\DisplayController as SiteSettingsDisplayController;
use App\Http\Controllers\Admin\SiteSettings\UpdateSubmitController as SiteSettingsUpdateSubmitController;
use Illuminate\Support\Facades\Route;


//  ADMIN
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', AdminDisplayController::class)->name('display');

        Route::get('/settings', SiteSettingsDisplayController::class)->name('settings');
        Route::post('/settings', SiteSettingsUpdateSubmitController::class);
    });
