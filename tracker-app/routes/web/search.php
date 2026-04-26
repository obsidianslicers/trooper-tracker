<?php

declare(strict_types=1);

use App\Http\Controllers\Search\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search', SearchController::class)
    ->name('search')
    ->middleware(['auth', 'verified', 'check.active']);
