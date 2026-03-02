<?php

declare(strict_types=1);

use App\Http\Controllers\Shares\ShareEventController;
use App\Http\Controllers\Shares\ShareEventRosterController;
use Illuminate\Support\Facades\Route;

Route::prefix('shares')
    ->name('shares.')
    ->group(function ()
    {
        Route::get('/event/{event}', ShareEventController::class)->name(name: 'event');
        Route::get('/roster/{share}', action: ShareEventRosterController::class)->name(name: 'roster');
    });