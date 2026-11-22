<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\EventTypes\Commands\DestroyEventTypeController;
use App\Http\Controllers\Admin\EventTypes\Commands\ReorderEventTypesController;
use App\Http\Controllers\Admin\EventTypes\Commands\StoreEventTypeController;
use App\Http\Controllers\Admin\EventTypes\Commands\UpdateEventTypeController;
use App\Http\Controllers\Admin\EventTypes\Pages\CreateEventTypeController;
use App\Http\Controllers\Admin\EventTypes\Pages\EditEventTypeController;
use App\Http\Controllers\Admin\EventTypes\Pages\IndexEventTypesController;
use Illuminate\Support\Facades\Route;

//  ADMIN/EVENT TYPES
Route::prefix('admin/event-types')
    ->name('admin.event-types.')
    ->middleware(['auth', 'check.role:staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', IndexEventTypesController::class)->name('index');
        Route::get('/create', CreateEventTypeController::class)->name('create');
        Route::post('/', StoreEventTypeController::class)->name('store');
        Route::post('/reorder', ReorderEventTypesController::class)->name('reorder');
        Route::get('/{event_type}/edit', EditEventTypeController::class)->name('edit');
        Route::post('/{event_type}', UpdateEventTypeController::class)->name('update');
        Route::post('/{event_type}/delete', DestroyEventTypeController::class)->name('destroy');
    });
