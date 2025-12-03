<?php

declare(strict_types=1);

use App\Http\Controllers\Account\CostumesDeleteHtmxController;
use App\Http\Controllers\Account\CostumesListController;
use App\Http\Controllers\Account\CostumesListHtmxController;
use App\Http\Controllers\Account\CostumesSubmitHtmxController;
use App\Http\Controllers\Account\NoticesListController;
use App\Http\Controllers\Account\NoticesSubmitHtmxController;
use App\Http\Controllers\Account\NotificationsListController;
use App\Http\Controllers\Account\NotificationsSubmitController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\ProfileSubmitController;
use Illuminate\Support\Facades\Route;

//  ACCOUNT
Route::prefix('account')
    ->name('account.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::post('/profile', ProfileSubmitController::class);
        Route::get('/notifications', NotificationsListController::class)->name('notifications');
        Route::post('/notifications', NotificationsSubmitController::class);
        Route::get('/notices', NoticesListController::class)->name('notices');
        Route::post('/notices-htmx/{notice}', NoticesSubmitHtmxController::class)->name('notices-htmx');
        Route::get('/costumes', CostumesListController::class)->name('costumes');
        Route::get('/costumes-htmx', CostumesListHtmxController::class)->name('costumes-htmx');
        Route::post('/costumes-htmx', CostumesSubmitHtmxController::class);
        Route::delete('/costumes-htmx', CostumesDeleteHtmxController::class);
    });
