<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Events\Commands\CopyEventController;
use App\Http\Controllers\Admin\Events\Commands\DestroyEventTrooperController;
use App\Http\Controllers\Admin\Events\Commands\DestroyEventUploadController;
use App\Http\Controllers\Admin\Events\Commands\DestroyShiftStationController;
use App\Http\Controllers\Admin\Events\Commands\ReorderShiftStationsController;
use App\Http\Controllers\Admin\Events\Commands\StoreEventController;
use App\Http\Controllers\Admin\Events\Commands\StoreEventTrooperController;
use App\Http\Controllers\Admin\Events\Commands\StoreEventUploadController;
use App\Http\Controllers\Admin\Events\Commands\ToggleEventUploadTypeController;
use App\Http\Controllers\Admin\Events\Commands\UpdateEventCharityController;
use App\Http\Controllers\Admin\Events\Commands\UpdateEventController;
use App\Http\Controllers\Admin\Events\Commands\UpdateEventShiftsController;
use App\Http\Controllers\Admin\Events\Commands\UpdateEventTroopersController;
use App\Http\Controllers\Admin\Events\Pages\CreateEventController;
use App\Http\Controllers\Admin\Events\Pages\EditEventController;
use App\Http\Controllers\Admin\Events\Pages\EditEventTabController;
use App\Http\Controllers\Admin\Events\Pages\IndexEventsController;
use App\Http\Controllers\Admin\Events\Pages\ListEventTrooperCostumesController;
use App\Http\Controllers\Admin\Events\Pages\ListEventTrooperOrganizationsController;
use App\Http\Controllers\Admin\Events\Pages\ShowCopyEventFormController;
use Illuminate\Support\Facades\Route;

//  ADMIN/EVENTS
Route::prefix('admin/events')
    ->name('admin.events.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner,event_coordinator'])
    ->group(function ()
    {
        Route::get('/', IndexEventsController::class)->name('index');
        Route::get('/create', CreateEventController::class)->name('create');
        Route::post('/', StoreEventController::class)->name('store');
        Route::get('/{event}/copy', ShowCopyEventFormController::class)->name('copy.create');
        Route::post('/{event}/copy', CopyEventController::class)->name('copy.store');
        Route::get('/{event}/edit', EditEventController::class)->name('edit');
        Route::post('/{event}', UpdateEventController::class)->name('update');

        Route::get('/{event}/shifts', EditEventTabController::class)->defaults('tab', 'shifts')->name('shifts.edit');
        Route::post('/{event}/shifts', UpdateEventShiftsController::class)->name('shifts.update');
        Route::post('/{event}/shifts/{event_shift}/stations/reorder', ReorderShiftStationsController::class)
            ->name('shifts.stations.reorder');
        Route::post('/{event}/shifts/{event_shift}/stations/{event_shift_station}/delete', DestroyShiftStationController::class)
            ->name('shifts.stations.destroy');
        Route::get('/{event}/charity', EditEventTabController::class)->defaults('tab', 'charity')->name('charity.edit');
        Route::post('/{event}/charity', UpdateEventCharityController::class)->name('charity.update');
        Route::get('/{event}/troopers', EditEventTabController::class)->defaults('tab', 'roster')->name('troopers.edit');
        Route::post('/{event}/troopers', UpdateEventTroopersController::class)->name('troopers.update');
        Route::get('/{event}/troopers/{event_trooper}/org-options', ListEventTrooperOrganizationsController::class)
            ->name('troopers.org-options');
        Route::get('/{event}/shifts/{event_shift}/troopers/costume-picker', ListEventTrooperCostumesController::class)
            ->name('troopers.costume-picker');
        Route::post('/{event}/shifts/{event_shift}/troopers', StoreEventTrooperController::class)->name('troopers.store');
        Route::post('/{event}/troopers/{event_trooper}/delete', DestroyEventTrooperController::class)->name('troopers.destroy');
        Route::get('/{event}/uploads', EditEventTabController::class)->defaults('tab', 'uploads')->name('uploads.index');
        Route::post('/{event}/uploads', StoreEventUploadController::class)->name('uploads.store');
        Route::post('/{event}/uploads/{event_upload}/toggle-type', ToggleEventUploadTypeController::class)
            ->name('uploads.toggle-type');
        Route::post('/{event}/uploads/{event_upload}/delete', DestroyEventUploadController::class)->name('uploads.destroy');
        Route::get('/{event}/mission-review', EditEventTabController::class)
            ->defaults('tab', 'mission-review')
            ->name('mission-review');
    });
