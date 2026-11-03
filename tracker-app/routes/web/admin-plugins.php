<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Plugins\DisableSubmitController;
use App\Http\Controllers\Admin\Plugins\EnableSubmitController;
use App\Http\Controllers\Admin\Plugins\ListController;
use App\Http\Controllers\Admin\Plugins\UpdateSettingsController;
use App\Http\Controllers\Admin\Plugins\UpdateSettingsSubmitController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/plugins')
    ->name('admin.plugins.')
    ->middleware(['auth', 'check.role:administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::post('/{slug}/enable', EnableSubmitController::class)->name('enable');
        Route::post('/{slug}/disable', DisableSubmitController::class)->name('disable');
        Route::get('/{slug}/settings', UpdateSettingsController::class)->name('settings');
        Route::post('/{slug}/settings', UpdateSettingsSubmitController::class);
    });
