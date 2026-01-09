<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShareEventController;
use App\Http\Controllers\ShareEventImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/share/{event}', ShareEventController::class)->name(name: 'share-event');