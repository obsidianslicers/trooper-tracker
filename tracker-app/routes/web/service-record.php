<?php

declare(strict_types=1);

use App\Http\Controllers\ServiceRecord\ServiceRecordDisplayController;
use Illuminate\Support\Facades\Route;

//  SERVICE RECORD
Route::prefix('service-record')
    ->name('service-record.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/', ServiceRecordDisplayController::class)->name('display');
    });