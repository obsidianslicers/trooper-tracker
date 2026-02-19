<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Costumes\CreateController;
use App\Http\Controllers\Admin\Costumes\CreateSubmitController;
use App\Http\Controllers\Admin\Costumes\ListController;
use App\Http\Controllers\Admin\Costumes\UpdateController;
use App\Http\Controllers\Admin\Costumes\UpdateSubmitController;
use Illuminate\Support\Facades\Route;

//  ADMIN/COSTUMES
Route::prefix('admin/costumes')
    ->name('admin.costumes.')
    ->middleware(['auth', 'check.role:administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/{parent}/create', CreateController::class)->name('create');
        Route::post('/{parent}/create', CreateSubmitController::class);
        Route::get('/{costume}/update', UpdateController::class)->name('update');
        Route::post('/{costume}/update', UpdateSubmitController::class);
    });
