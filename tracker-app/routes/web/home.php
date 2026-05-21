<?php

declare(strict_types=1);

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/faq', FaqController::class)->name('faq');
