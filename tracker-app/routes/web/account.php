<?php

declare(strict_types=1);

use App\Http\Controllers\Account\CostumesDeleteHtmxController;
use App\Http\Controllers\Account\CostumesListController;
use App\Http\Controllers\Account\CostumesListHtmxController;
use App\Http\Controllers\Account\CostumesSubmitHtmxController;
use App\Http\Controllers\Account\NoticesListController;
use App\Http\Controllers\Account\NoticesSubmitHtmxController;
<<<<<<< HEAD
use App\Http\Controllers\Account\NotificationsListController;
use App\Http\Controllers\Account\NotificationsSubmitController;
=======
use App\Http\Controllers\Account\NotificationsListHtmxController;
use App\Http\Controllers\Account\NotificationsSubmitHtmxController;
>>>>>>> b60e060 (feature: add notice board)
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\ProfileSubmitController;
use Illuminate\Support\Facades\Route;

//  ACCOUNT
Route::prefix('account')
    ->name('account.')
    ->middleware('auth')
    ->group(function ()
    {
<<<<<<< HEAD
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::post('/profile', ProfileSubmitController::class);
        Route::get('/notifications', NotificationsListController::class)->name('notifications');
        Route::post('/notifications', NotificationsSubmitController::class);
        Route::get('/notices', NoticesListController::class)->name('notices');
        Route::post('/notices-htmx/{notice}', NoticesSubmitHtmxController::class)->name('notices-htmx');
        Route::get('/costumes', CostumesListController::class)->name('costumes');
=======
        Route::get('/', ProfileController::class)->name('display');
        Route::get('/notices', NoticesListController::class)->name('notices');
        Route::post('/notices-htmx/{notice}', NoticesSubmitHtmxController::class)->name('notices-htmx');
        Route::post('/profile-htmx', ProfileSubmitHtmxController::class)->name('profile-htmx');
        Route::get('/notifications-htmx', NotificationsListHtmxController::class)->name('notifications-htmx');
        Route::post('/notifications-htmx', NotificationsSubmitHtmxController::class);
>>>>>>> b60e060 (feature: add notice board)
        Route::get('/costumes-htmx', CostumesListHtmxController::class)->name('costumes-htmx');
        Route::post('/costumes-htmx', CostumesSubmitHtmxController::class);
        Route::delete('/costumes-htmx', CostumesDeleteHtmxController::class);
    });
