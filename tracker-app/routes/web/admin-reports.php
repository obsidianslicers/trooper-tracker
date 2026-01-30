<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Reports\EventSummaryController;
use App\Http\Controllers\Admin\Reports\EventTypeCountController;
use App\Http\Controllers\Admin\Reports\ReportDisplayController;
use App\Http\Controllers\Admin\Reports\StatusChangeLogController;
use App\Http\Controllers\Admin\Reports\TrooperEventSummaryController;
use App\Http\Controllers\Admin\Reports\TroopersWithoutActivityController;
use Illuminate\Support\Facades\Route;


//  ADMIN/TROOPERs
Route::prefix('admin/reports')
    ->name('admin.reports.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ReportDisplayController::class)->name('display');
        Route::get('/status-change-log', StatusChangeLogController::class)->name('status-change-log');
        Route::get('/troopers-without-activity', TroopersWithoutActivityController::class)->name('troopers-without-activity');
        Route::get('/event-type-count', EventTypeCountController::class)->name('event-type-count');
        Route::get('/event-summary', EventSummaryController::class)->name('event-summary');
        Route::get('/trooper-event-summary', TrooperEventSummaryController::class)->name('trooper-event-summary');
    });
