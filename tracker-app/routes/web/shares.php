<?php

declare(strict_types=1);

use App\Http\Controllers\Shares\Pages\ShowOrganizationShareImageController;
use App\Http\Controllers\Shares\Pages\ShowSharedEventController;
use App\Http\Controllers\Shares\Pages\ShowSharedRosterController;
use Illuminate\Support\Facades\Route;

Route::prefix('shares')
    ->name('shares.')
    ->group(function ()
    {
        Route::get('/event/{event}', ShowSharedEventController::class)->name(name: 'event');
        Route::get('/event/{event}/{event_upload}', ShowSharedEventController::class)->name(name: 'event-upload');
        Route::get('/og-image/{organization}', ShowOrganizationShareImageController::class)->name(name: 'og-image');
        Route::get('/roster/{share}', action: ShowSharedRosterController::class)->name(name: 'roster');
    });
