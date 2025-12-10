<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Awards\CreateController;
use App\Http\Controllers\Admin\Awards\CreateSubmitController;
use App\Http\Controllers\Admin\Awards\ListController;
use App\Http\Controllers\Admin\Awards\ListTroopersController;
use App\Http\Controllers\Admin\Awards\UpdateController;
use App\Http\Controllers\Admin\Awards\UpdateSubmitController;
use Illuminate\Support\Facades\Route;

//  ADMIN/NOTICES
Route::prefix('admin/awards')
    ->name('admin.awards.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/create', CreateSubmitController::class);
        Route::get('/{award}/update', UpdateController::class)->name('update');
        Route::post('/{award}/update', UpdateSubmitController::class);
        Route::get('/{award}/troopers', ListTroopersController::class)->name('list-troopers');
    });
