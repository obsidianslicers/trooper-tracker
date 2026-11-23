<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Reports\Pages\IndexReportsController;
use App\Http\Controllers\Admin\Reports\Pages\ShowCostumeEventSummaryController;
use App\Http\Controllers\Admin\Reports\Pages\ShowDonationEventSummaryController;
use App\Http\Controllers\Admin\Reports\Pages\ShowEventSummaryController;
use App\Http\Controllers\Admin\Reports\Pages\ShowEventTypeCountController;
use App\Http\Controllers\Admin\Reports\Pages\ShowStatusChangeLogController;
use App\Http\Controllers\Admin\Reports\Pages\ShowTrooperEventSummaryController;
use App\Http\Controllers\Admin\Reports\Pages\ShowTroopersWithoutActivityController;
use Illuminate\Support\Facades\Route;

//  ADMIN/TROOPERs
Route::prefix('admin/reports')
    ->name('admin.reports.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', IndexReportsController::class)->name('index');
        Route::get('/status-change-log', ShowStatusChangeLogController::class)->name('status-change-log');
        Route::get('/troopers-without-activity', ShowTroopersWithoutActivityController::class)->name('troopers-without-activity');
        Route::get('/event-type-count', ShowEventTypeCountController::class)->name('event-type-count');
        Route::get('/event-summary', ShowEventSummaryController::class)->name('event-summary');
        Route::get('/trooper-event-summary', ShowTrooperEventSummaryController::class)->name('trooper-event-summary');
        Route::get('/donation-event-summary', ShowDonationEventSummaryController::class)->name('donation-event-summary');
        Route::get('/costume-event-summary', ShowCostumeEventSummaryController::class)->name('costume-event-summary');
    });
