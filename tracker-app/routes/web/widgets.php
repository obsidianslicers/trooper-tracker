<?php

declare(strict_types=1);

use App\Http\Controllers\Widgets\NoticeDisplayHtmxController;
use App\Http\Controllers\Widgets\SupportDisplayHtmxController;
use Illuminate\Support\Facades\Route;

//  ACCOUNT
Route::prefix('widgets')
    ->name('widgets.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/support-htmx', SupportDisplayHtmxController::class)->name('support-htmx');
        Route::get('/notices-htmx', NoticeDisplayHtmxController::class)->name('notices-htmx');
    });