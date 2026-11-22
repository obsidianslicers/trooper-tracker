<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Awards\Commands\DestroyAwardTrooperController;
use App\Http\Controllers\Admin\Awards\Commands\StoreAwardController;
use App\Http\Controllers\Admin\Awards\Commands\StoreAwardTrooperController;
use App\Http\Controllers\Admin\Awards\Commands\UpdateAwardController;
use App\Http\Controllers\Admin\Awards\Pages\CreateAwardController;
use App\Http\Controllers\Admin\Awards\Pages\CreateAwardTrooperController;
use App\Http\Controllers\Admin\Awards\Pages\EditAwardController;
use App\Http\Controllers\Admin\Awards\Pages\IndexAwardsController;
use App\Http\Controllers\Admin\Awards\Pages\IndexAwardTroopersController;
use Illuminate\Support\Facades\Route;

//  ADMIN/AWARDS
Route::prefix('admin/awards')
    ->name('admin.awards.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', IndexAwardsController::class)->name('index');
        Route::get('/create', CreateAwardController::class)->name('create');
        Route::post('/', StoreAwardController::class)->name('store');
        Route::get('/{award}/edit', EditAwardController::class)->name('edit');
        Route::post('/{award}', UpdateAwardController::class)->name('update');
        Route::get('/{award}/troopers', IndexAwardTroopersController::class)->name('troopers.index');
        Route::get('/{award}/troopers/create', CreateAwardTrooperController::class)->name('troopers.create');
        Route::post('/{award}/troopers', StoreAwardTrooperController::class)->name('troopers.store');
        Route::post('/{award}/troopers/delete', DestroyAwardTrooperController::class)->name('troopers.destroy');
    });
