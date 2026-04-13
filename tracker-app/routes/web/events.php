<?php

declare(strict_types=1);

use App\Http\Controllers\Events\CalendarController;
use App\Http\Controllers\Events\CancelledController;
use App\Http\Controllers\Events\ClosedController;
use App\Http\Controllers\Events\DownloadRosterCsvController;
use App\Http\Controllers\Events\EventDisplayController;
use App\Http\Controllers\Events\GuestSignUpHtmxController;
use App\Http\Controllers\Events\ListController;
use App\Http\Controllers\Events\MapController;
use App\Http\Controllers\Events\ShareRosterHtmxController;
use App\Http\Controllers\Events\ShiftCompleteController;
use App\Http\Controllers\Events\SignUpHtmxController;
use App\Http\Controllers\Events\SignUpUpdateHtmxController;
use App\Http\Controllers\Events\GuestUpdateHtmxController;
use App\Http\Controllers\Events\ForumReplyController;
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
        Route::get('/details/{event}/roster-csv', [DownloadRosterCsvController::class, 'allShifts'])->name('download-roster-csv');
        Route::get('/details/{event}/shifts/{event_shift}/roster-csv', [DownloadRosterCsvController::class, 'singleShift'])->name('download-shift-roster-csv');
        Route::post('/upload/{event}', UploadImageController::class)->name('upload-image');
        Route::post('/forum-reply/{event}', ForumReplyController::class)->name('forum-reply-htmx');
        Route::post('/share-roster/{event}', ShareRosterHtmxController::class)->name('share-roster-htmx');
        Route::post('/signup/{event_shift}/trooper', SignUpHtmxController::class)->name('signup-htmx');
        Route::post('/signup/{event_shift}/guest', GuestSignUpHtmxController::class)->name('guest-signup-htmx');
        Route::post('/update/{event_trooper}/trooper', SignUpUpdateHtmxController::class)->name('signup-update-htmx');
        Route::post('/update/{event_guest}/guest', GuestUpdateHtmxController::class)->name('guest-update-htmx');
        Route::get('/complete/{event_trooper}/{status}', ShiftCompleteController::class)->name('shift-complete');
    });