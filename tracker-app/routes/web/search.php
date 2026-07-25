<?php

declare(strict_types=1);

use App\Http\Controllers\Search\SearchController;
use Illuminate\Support\Facades\Route;
Route::prefix('search')
    ->name('search.')
    ->middleware(['auth', 'verified', 'check.active'])
    ->group(function ()
    {
        Route::get('/all', SearchController::class)->name('all');
    });
