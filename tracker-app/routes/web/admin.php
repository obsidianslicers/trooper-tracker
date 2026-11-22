<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Pages\ShowDashboardController;
use App\Http\Controllers\Admin\Pages\ShowSystemCheckController;
use Illuminate\Support\Facades\Route;

//  ADMIN
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', ShowDashboardController::class)->name('dashboard');

        Route::get('/system-check', ShowSystemCheckController::class)
            ->name('system-check')
            ->middleware('check.role:system_owner');
    });
