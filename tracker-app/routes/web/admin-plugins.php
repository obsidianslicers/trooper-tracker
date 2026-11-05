<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Plugins\AddPackageSubmitController;
use App\Http\Controllers\Admin\Plugins\DisableSubmitController;
use App\Http\Controllers\Admin\Plugins\EnableSubmitController;
use App\Http\Controllers\Admin\Plugins\InstallSubmitController;
use App\Http\Controllers\Admin\Plugins\ListController;
use App\Http\Controllers\Admin\Plugins\RemovePackageSubmitController;
use App\Http\Controllers\Admin\Plugins\UninstallSubmitController;
use App\Http\Controllers\Admin\Plugins\UpdateSettingsController;
use App\Http\Controllers\Admin\Plugins\UpdateSettingsSubmitController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/plugins')
    ->name('admin.plugins.')
    ->middleware(['auth', 'check.role:administrator'])
    ->group(function (): void
    {
        Route::get('/', ListController::class)->name('list');
        Route::post('/install', InstallSubmitController::class)->name('install');
        Route::post('/packages', AddPackageSubmitController::class)->name('packages.add');
        Route::post('/packages/{package}/remove', RemovePackageSubmitController::class)
            ->name('packages.remove');
        Route::post('/{slug}/enable', EnableSubmitController::class)->name('enable');
        Route::post('/{slug}/disable', DisableSubmitController::class)->name('disable');
        Route::post('/{slug}/uninstall', UninstallSubmitController::class)->name('uninstall');
        Route::get('/{slug}/settings', UpdateSettingsController::class)->name('settings');
        Route::post('/{slug}/settings', UpdateSettingsSubmitController::class);
    });
