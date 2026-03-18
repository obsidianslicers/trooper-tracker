<?php

declare(strict_types=1);

use App\Http\Controllers\Verifications\VerifyEmailController;
use App\Http\Controllers\Verifications\VerifyNoticeController;
use App\Http\Controllers\Verifications\VerifyNoticeSubmitController;
use Illuminate\Support\Facades\Route;

// VERIFICATION
Route::prefix('verification')
    ->name('verification.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/email/{id}/{hash}', VerifyEmailController::class)->middleware('signed')->name('verify');
        Route::get('/notice', VerifyNoticeController::class)->name('notice');
        Route::post('/notice', VerifyNoticeSubmitController::class)->middleware('throttle:6,1');
    });