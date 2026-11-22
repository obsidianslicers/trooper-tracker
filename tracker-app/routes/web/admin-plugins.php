<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Plugins\Commands\DisablePluginController;
use App\Http\Controllers\Admin\Plugins\Commands\EnablePluginController;
use App\Http\Controllers\Admin\Plugins\Commands\InstallPluginController;
use App\Http\Controllers\Admin\Plugins\Commands\UninstallPluginController;
use App\Http\Controllers\Admin\Plugins\Commands\UpdatePluginSettingsController;
use App\Http\Controllers\Admin\Plugins\Pages\EditPluginSettingsController;
use App\Http\Controllers\Admin\Plugins\Pages\IndexPluginsController;
use Illuminate\Support\Facades\Route;

//  ADMIN/PLUGINS
Route::prefix('admin/plugins')
    ->name('admin.plugins.')
    ->middleware(['auth', 'check.role:system_owner'])
    ->group(function (): void
    {
        Route::get('/', IndexPluginsController::class)->name('index');
        Route::post('/install', InstallPluginController::class)->name('install');
        Route::post('/{slug}/enable', EnablePluginController::class)->name('enable');
        Route::post('/{slug}/disable', DisablePluginController::class)->name('disable');
        Route::post('/{slug}/uninstall', UninstallPluginController::class)->name('uninstall');
        Route::get('/{slug}/settings', EditPluginSettingsController::class)->name('settings.edit');
        Route::post('/{slug}/settings', UpdatePluginSettingsController::class)->name('settings.update');
    });
