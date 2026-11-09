<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\EventTypes\Commands\CreateSubmitController;
use App\Http\Controllers\Admin\EventTypes\Commands\DeleteSubmitController;
use App\Http\Controllers\Admin\EventTypes\Commands\ReorderSubmitController;
use App\Http\Controllers\Admin\EventTypes\Commands\UpdateSubmitController;
use App\Http\Controllers\Admin\EventTypes\Pages\CreateController;
use App\Http\Controllers\Admin\EventTypes\Pages\IndexController;
use App\Http\Controllers\Admin\EventTypes\Pages\UpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/event-types')
    ->name('admin.event-types.')
    ->middleware(['auth', 'check.role:administrator'])
    ->group(function ()
    {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/create', CreateSubmitController::class);
        Route::post('/reorder', ReorderSubmitController::class)->name('reorder');
        Route::get('/{event_type}/update', UpdateController::class)->name('update');
        Route::post('/{event_type}/update', UpdateSubmitController::class);
        Route::post('/{event_type}/delete', DeleteSubmitController::class)->name('delete');
    });
