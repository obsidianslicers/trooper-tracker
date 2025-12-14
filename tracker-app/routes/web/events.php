<?php

declare(strict_types=1);

use App\Http\Controllers\Events\ListController;
use App\Http\Controllers\Events\SignUpController;
use App\Http\Controllers\Events\SignUpHtmxController;
use App\Http\Controllers\Events\SignUpUpdateHtmxController;
use Illuminate\Support\Facades\Route;

//  DASHBOARD
Route::prefix('events')
    ->name('events.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/{event}', SignUpController::class)->name('signup');
        Route::post('/signup/{event_shift}', SignUpHtmxController::class)->name('signup-htmx');
        Route::post('/signup/{event_trooper}/trooper', SignUpUpdateHtmxController::class)->name('signup-update-htmx');
    });