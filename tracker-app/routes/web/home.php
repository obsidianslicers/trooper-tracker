<?php

declare(strict_types=1);

use App\Http\Controllers\Home\Pages\ShowFaqController;
use App\Http\Controllers\Home\Pages\ShowHomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', ShowHomeController::class)->name('home');
Route::get('/faq', ShowFaqController::class)->name('faq');
