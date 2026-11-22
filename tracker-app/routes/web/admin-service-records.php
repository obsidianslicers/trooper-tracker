<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ServiceRecords\Commands\AssignEventTrooperCreditController;
use App\Http\Controllers\Admin\ServiceRecords\Pages\IndexMissingCreditsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/service-records')
    ->name('admin.service-records.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/missing-credits', IndexMissingCreditsController::class)->name('missing-credits.index');
        Route::post('/missing-credits/{event_trooper}/assign', AssignEventTrooperCreditController::class)
            ->name('missing-credits.assign');
    });
