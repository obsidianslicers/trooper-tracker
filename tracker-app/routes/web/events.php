<?php

declare(strict_types=1);

use App\Http\Controllers\Events\CalendarController;
use App\Http\Controllers\Events\EventDisplayController;
use App\Http\Controllers\Events\ListController;
use App\Http\Controllers\Events\ShiftCompleteController;
use App\Http\Controllers\Events\SignUpHtmxController;
use App\Http\Controllers\Events\SignUpUpdateHtmxController;
use App\Http\Controllers\Events\UploadImageController;
use Illuminate\Support\Facades\Route;

//  DASHBOARD
Route::prefix('events')
    ->name('events.')
    ->middleware(['auth', 'check.active'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/calendar', CalendarController::class)->name('calendar');
        Route::get('/display/{event}', EventDisplayController::class)->name('display');
        Route::post('/upload/{event}', UploadImageController::class)->name('upload-image');
        Route::post('/signup/{event_shift}', SignUpHtmxController::class)->name('signup-htmx');
        Route::post('/signup/{event_trooper}/trooper', SignUpUpdateHtmxController::class)->name('signup-update-htmx');
        Route::get('/complete/{event_trooper}/{status}', ShiftCompleteController::class)->name('shift-complete');
    });