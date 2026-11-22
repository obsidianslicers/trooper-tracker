<?php

declare(strict_types=1);

use App\Http\Controllers\Events\AddTrooperFormController;
use App\Http\Controllers\Events\BroadcastMessageController;
use App\Http\Controllers\Events\CalendarController;
use App\Http\Controllers\Events\CancelledController;
use App\Http\Controllers\Events\ClosedController;
use App\Http\Controllers\Events\DownloadEventIcsController;
use App\Http\Controllers\Events\DownloadEventShiftIcsController;
use App\Http\Controllers\Events\DownloadRosterCsvController;
use App\Http\Controllers\Events\EventDisplayController;
use App\Http\Controllers\Events\GuestSignUpController;
use App\Http\Controllers\Events\GuestUpdateController;
use App\Http\Controllers\Events\ListController;
use App\Http\Controllers\Events\MapController;
use App\Http\Controllers\Events\MissionBriefAcknowledgeController;
use App\Http\Controllers\Events\ShareRosterController;
use App\Http\Controllers\Events\ShiftCompleteClubController;
use App\Http\Controllers\Events\ShiftCompleteController;
use App\Http\Controllers\Events\SignUpController;
use App\Http\Controllers\Events\SignUpUpdateController;
use App\Http\Controllers\Events\ToggleEventUploadTagController;
use App\Http\Controllers\Events\ToggleEventWatchController;
use App\Http\Controllers\Events\ToggleUploadTypeController;
use App\Http\Controllers\Events\UploadImageController;
use Illuminate\Support\Facades\Route;

//  DASHBOARD
Route::prefix('events')
    ->name('events.')
    ->middleware(['auth', 'verified', 'check.active'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/calendar', CalendarController::class)->name('calendar');
        Route::get('/map', MapController::class)->name('map');
        Route::get('/closed', ClosedController::class)->name('closed');
        Route::get('/cancelled', CancelledController::class)->name('cancelled');
        Route::get('/details/{event}', EventDisplayController::class)->name('display');
        Route::redirect('/{event}', '/events/details/{event}')->whereNumber('event');
        Route::post('/details/{event}/watch', ToggleEventWatchController::class)->name('toggle-watch');
        Route::post('/details/{event}/ack-mission-brief', MissionBriefAcknowledgeController::class)->name('ack-mission-brief');
        Route::get('/details/{event}/calendar.ics', DownloadEventIcsController::class)->name('download-ics');
        Route::get('/details/{event}/shifts/{event_shift}/calendar.ics', DownloadEventShiftIcsController::class)->name('download-shift-ics');
        Route::get('/details/{event}/roster-csv', [DownloadRosterCsvController::class, 'allShifts'])->name('download-roster-csv');
        Route::get('/details/{event}/shifts/{event_shift}/roster-csv', [DownloadRosterCsvController::class, 'singleShift'])->name('download-shift-roster-csv');
        Route::post('/upload/{event}', UploadImageController::class)->name('upload-image');
        Route::post('/uploads/{event_upload}/toggle-tag', ToggleEventUploadTagController::class)->name('toggle-upload-tag');
        Route::post('/uploads/{event_upload}/toggle-type', ToggleUploadTypeController::class)->name('toggle-upload-type');
        Route::post('/share-roster/{event}', ShareRosterController::class)->name('share-roster');
        Route::post('/broadcast-message/{event}', BroadcastMessageController::class)->name('broadcast-message');
        Route::get('/signup/{event_shift}/add-trooper-form', AddTrooperFormController::class)->name('add-trooper-form');
        Route::post('/signup/{event_shift}/trooper', SignUpController::class)->name('signup');
        Route::post('/signup/{event_shift}/guest', GuestSignUpController::class)->name('guest-signup');
        Route::post('/update/{event_trooper}/trooper', SignUpUpdateController::class)->name('signup-update');
        Route::post('/update/{event_guest}/guest', GuestUpdateController::class)->name('guest-update');
        Route::get('/complete/{event_trooper}/{status}', ShiftCompleteController::class)->name('shift-complete');
        Route::post('/complete/{event_trooper}/club-select', ShiftCompleteClubController::class)->name('shift-complete-club-select');
    });
