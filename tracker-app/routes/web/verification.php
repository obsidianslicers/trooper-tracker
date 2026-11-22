<?php

declare(strict_types=1);

use App\Http\Controllers\Verifications\Commands\ResendVerificationEmailController;
use App\Http\Controllers\Verifications\Commands\VerifyEmailController;
use App\Http\Controllers\Verifications\Pages\ShowVerifyEmailNoticeController;
use Illuminate\Support\Facades\Route;

// VERIFICATION
Route::prefix('verification')
    ->name('verification.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/email/{id}/{hash}', VerifyEmailController::class)->middleware('signed')->name('verify');
        Route::get('/notice', ShowVerifyEmailNoticeController::class)->name('notice');
        Route::post('/notice', ResendVerificationEmailController::class)->middleware('throttle:6,1');
    });
