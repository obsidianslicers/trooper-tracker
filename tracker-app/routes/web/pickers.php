<?php

declare(strict_types=1);

use App\Http\Controllers\Pickers\OrganizationPickerController;
use App\Http\Controllers\Pickers\TrooperPickerController;

//  ACCOUNT
Route::prefix('pickers')
    ->name('pickers.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/organization', OrganizationPickerController::class)->name('organization');
        Route::get('/trooper', TrooperPickerController::class)->name('trooper');
    });
