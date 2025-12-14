<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Events\CreateController;
use App\Http\Controllers\Admin\Events\CreateSubmitController;
use App\Http\Controllers\Admin\Events\ListController;
use App\Http\Controllers\Admin\Events\UpdateController;
use App\Http\Controllers\Admin\Events\UpdateShiftsController;
use App\Http\Controllers\Admin\Events\UpdateShiftsSubmitController;
use App\Http\Controllers\Admin\Events\UpdateSubmitController;
use App\Http\Controllers\Admin\Events\UpdateTroopersController;
use App\Http\Controllers\Admin\Events\UpdateTroopersSubmitController;
use Illuminate\Support\Facades\Route;

//  ADMIN/EVENTS
Route::prefix('admin/events')
    ->name('admin.events.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/create', CreateSubmitController::class);
        Route::get('/{event}/update', UpdateController::class)->name('update');
        Route::post('/{event}/update', UpdateSubmitController::class);
        Route::get('/{event}/shifts', UpdateShiftsController::class)->name('shifts');
        Route::post('/{event}/shifts', UpdateShiftsSubmitController::class);
        Route::get('/{event}/troopers', UpdateTroopersController::class)->name('troopers');
        Route::post('/{event}/troopers', UpdateTroopersSubmitController::class);
    });
