<?php

declare(strict_types=1);

use App\Http\Controllers\Shares\ShareEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('shares')
    ->name('shares.')
    ->group(function ()
    {
        Route::get('/share/event/{event}', ShareEventController::class)->name(name: 'event');
        // Route::get('/share/roster/{share}', ShareRosterController::class)->name(name: 'roster');
    });