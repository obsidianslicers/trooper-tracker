<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ServiceRecords\AssignCreditController;
use App\Http\Controllers\Admin\ServiceRecords\MissingCreditsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/service-records')
    ->name('admin.service-records.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function () {
        Route::get('/missing-credits', MissingCreditsController::class)->name('missing-credits');
        Route::post('/missing-credits/{event_trooper}/assign', AssignCreditController::class)
            ->name('missing-credits.assign');
    });
