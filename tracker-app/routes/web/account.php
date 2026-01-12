<?php

declare(strict_types=1);

use App\Http\Controllers\Account\CostumesController;
use App\Http\Controllers\Account\CostumesDeleteHtmxController;
use App\Http\Controllers\Account\CostumesSubmitHtmxController;
use App\Http\Controllers\Account\NoticesController;
use App\Http\Controllers\Account\NoticesSubmitHtmxController;
use App\Http\Controllers\Account\NotificationsController;
use App\Http\Controllers\Account\NotificationsSubmitController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\ProfileSubmitController;
use App\Http\Controllers\Account\SetupController;
use App\Http\Controllers\Account\SetupSubmitController;
use Illuminate\Support\Facades\Route;

//  ACCOUNT
Route::prefix('account')
    ->name('account.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::post('/profile', ProfileSubmitController::class);
        Route::get('/notifications', NotificationsController::class)->name('notifications');
        Route::post('/notifications', NotificationsSubmitController::class);
        Route::get('/notices', NoticesController::class)->name('notices');
        Route::post('/notices-htmx/{notice}', NoticesSubmitHtmxController::class)->name('notices-htmx');
        Route::get('/costumes', CostumesController::class)->name('costumes');
        Route::post('/costumes-htmx', CostumesSubmitHtmxController::class)->name('costumes-htmx');
        Route::delete('/costumes-htmx', CostumesDeleteHtmxController::class);

        //  needed a post name to get the middleware to work properly
        Route::get('/setup', SetupController::class)->name('setup');
        Route::post('/setup', SetupSubmitController::class)->name('setup-submit');
    });
