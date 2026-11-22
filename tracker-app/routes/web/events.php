<?php

declare(strict_types=1);

use App\Http\Controllers\Events\Commands\AcknowledgeMissionBriefController;
use App\Http\Controllers\Events\Commands\BroadcastEventMessageController;
use App\Http\Controllers\Events\Commands\CompleteShiftController;
use App\Http\Controllers\Events\Commands\CompleteShiftWithClubController;
use App\Http\Controllers\Events\Commands\ConfirmShiftAttendanceController;
use App\Http\Controllers\Events\Commands\ShareEventRosterController;
use App\Http\Controllers\Events\Commands\SignUpGuestController;
use App\Http\Controllers\Events\Commands\SignUpTrooperController;
use App\Http\Controllers\Events\Commands\ToggleEventUploadTagController;
use App\Http\Controllers\Events\Commands\ToggleEventUploadTypeController;
use App\Http\Controllers\Events\Commands\ToggleEventWatchController;
use App\Http\Controllers\Events\Commands\UpdateGuestSignUpController;
use App\Http\Controllers\Events\Commands\UpdateTrooperSignUpController;
use App\Http\Controllers\Events\Commands\UploadEventImagesController;
use App\Http\Controllers\Events\Pages\DownloadEventIcsController;
use App\Http\Controllers\Events\Pages\DownloadEventShiftIcsController;
use App\Http\Controllers\Events\Pages\DownloadRosterCsvController;
use App\Http\Controllers\Events\Pages\ListCancelledEventsController;
use App\Http\Controllers\Events\Pages\ListClosedEventsController;
use App\Http\Controllers\Events\Pages\ListEventsController;
use App\Http\Controllers\Events\Pages\ListTroopsToConfirmController;
use App\Http\Controllers\Events\Pages\ShowAddTrooperFormController;
use App\Http\Controllers\Events\Pages\ShowEventCalendarController;
use App\Http\Controllers\Events\Pages\ShowEventController;
use App\Http\Controllers\Events\Pages\ShowEventMapController;
use Illuminate\Support\Facades\Route;

//  DASHBOARD
Route::prefix('events')
    ->name('events.')
    ->middleware(['auth', 'verified', 'check.active'])
    ->group(function ()
    {
        Route::get('/', ListEventsController::class)->name('list');
        Route::get('/calendar', ShowEventCalendarController::class)->name('calendar');
        Route::get('/map', ShowEventMapController::class)->name('map');
        Route::get('/closed', ListClosedEventsController::class)->name('closed');
        Route::get('/cancelled', ListCancelledEventsController::class)->name('cancelled');
        Route::get('/details/{event}', ShowEventController::class)->name('display');
        Route::redirect('/{event}', '/events/details/{event}')->whereNumber('event');
        Route::post('/details/{event}/watch', ToggleEventWatchController::class)->name('toggle-watch');
        Route::post('/details/{event}/ack-mission-brief', AcknowledgeMissionBriefController::class)->name('ack-mission-brief');
        Route::get('/details/{event}/calendar.ics', DownloadEventIcsController::class)->name('download-ics');
        Route::get('/details/{event}/shifts/{event_shift}/calendar.ics', DownloadEventShiftIcsController::class)->name('download-shift-ics');
        Route::get('/details/{event}/roster-csv', [DownloadRosterCsvController::class, 'allShifts'])->name('download-roster-csv');
        Route::get('/details/{event}/shifts/{event_shift}/roster-csv', [DownloadRosterCsvController::class, 'singleShift'])->name('download-shift-roster-csv');
        Route::post('/upload/{event}', UploadEventImagesController::class)->name('upload-image');
        Route::post('/uploads/{event_upload}/toggle-tag', ToggleEventUploadTagController::class)->name('toggle-upload-tag');
        Route::post('/uploads/{event_upload}/toggle-type', ToggleEventUploadTypeController::class)->name('toggle-upload-type');
        Route::post('/share-roster/{event}', ShareEventRosterController::class)->name('share-roster');
        Route::post('/broadcast-message/{event}', BroadcastEventMessageController::class)->name('broadcast-message');
        Route::get('/signup/{event_shift}/add-trooper-form', ShowAddTrooperFormController::class)->name('add-trooper-form');
        Route::post('/signup/{event_shift}/trooper', SignUpTrooperController::class)->name('signup');
        Route::post('/signup/{event_shift}/guest', SignUpGuestController::class)->name('guest-signup');
        Route::post('/update/{event_trooper}/trooper', UpdateTrooperSignUpController::class)->name('signup-update');
        Route::post('/update/{event_guest}/guest', UpdateGuestSignUpController::class)->name('guest-update');
        Route::get('/complete/{event_trooper}/{status}', CompleteShiftController::class)->name('shift-complete');
        Route::post('/complete/{event_trooper}/club-select', CompleteShiftWithClubController::class)->name('shift-complete-club-select');
        Route::get('/to-confirm', ListTroopsToConfirmController::class)->name('troops-to-confirm');
        Route::post('/confirm/{event_trooper}', ConfirmShiftAttendanceController::class)->name('confirm-attendance');
    });
