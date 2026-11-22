<?php

declare(strict_types=1);

use App\Http\Controllers\Widgets\Pages\ShowNoticeHtmxController;
use App\Http\Controllers\Widgets\Pages\ShowSupportGoalHtmxController;
use Illuminate\Support\Facades\Route;

//  ACCOUNT
Route::prefix('widgets')
    ->name('widgets.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/support-htmx', ShowSupportGoalHtmxController::class)->name('support-htmx');
        Route::get('/notices-htmx', ShowNoticeHtmxController::class)->name('notices-htmx');
    });
