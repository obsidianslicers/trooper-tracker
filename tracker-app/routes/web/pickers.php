<?php

declare(strict_types=1);

use App\Http\Controllers\Pickers\Pages\ListPickerOrganizationsController;
use App\Http\Controllers\Pickers\Pages\ListPickerTroopersController;

//  ACCOUNT
Route::prefix('pickers')
    ->name('pickers.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/organization', ListPickerOrganizationsController::class)->name('organization');
        Route::get('/trooper', ListPickerTroopersController::class)->name('trooper');
    });
