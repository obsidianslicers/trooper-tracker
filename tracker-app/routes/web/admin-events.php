<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Events\ListController;
use App\Http\Controllers\Admin\Events\UpdateController;
use App\Http\Controllers\Admin\Events\UpdateHtmxController;
use App\Http\Controllers\Admin\Events\UpdateOrganizationsController;
use App\Http\Controllers\Admin\Events\UpdateOrganizationsSubmitController;
use App\Http\Controllers\Admin\Events\UpdateSubmitController;
use App\Http\Controllers\Admin\Events\UpdateVenueController;
use App\Http\Controllers\Admin\Events\UpdateVenueSubmitController;
use Illuminate\Support\Facades\Route;

//  ADMIN/EVENTS
Route::prefix('admin/events')
    ->name('admin.events.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        // Route::get('/create', CreateController::class)->name('create');
        // Route::post('/create', CreateSubmitController::class);
        Route::get('/{event}/update', UpdateController::class)->name('update');
        Route::post('/{event}/update-htmx', UpdateHtmxController::class)->name('update-htmx');
        Route::post('/{event}/update', UpdateSubmitController::class);
        Route::get('/{event}/organizations', UpdateOrganizationsController::class)->name('organizations');
        Route::post('/{event}/organizations', UpdateOrganizationsSubmitController::class);
        Route::get('/{event}/venue', UpdateVenueController::class)->name('venue');
        Route::post('/{event}/venue', UpdateVenueSubmitController::class)->name('venue');
    });
