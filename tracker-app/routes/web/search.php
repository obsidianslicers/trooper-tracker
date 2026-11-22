<?php

declare(strict_types=1);

use App\Http\Controllers\Search\Pages\SearchAllController;
use App\Http\Controllers\Search\Pages\SearchCostumesController;
use App\Http\Controllers\Search\Pages\SearchTroopersController;
use Illuminate\Support\Facades\Route;

Route::prefix('search')
    ->name('search.')
    ->middleware(['auth', 'verified', 'check.active'])
    ->group(function ()
    {
        Route::get('/troopers', SearchTroopersController::class)->name('troopers');
        Route::get('/costumes', SearchCostumesController::class)->name('costumes');
        Route::get('/all', SearchAllController::class)->name('all');
    });
