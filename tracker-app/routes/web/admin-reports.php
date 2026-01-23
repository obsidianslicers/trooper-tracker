<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Reports\ReportDisplayController;
use Illuminate\Support\Facades\Route;


//  ADMIN/TROOPERs
Route::prefix('admin/reports')
    ->name('admin.reports.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ReportDisplayController::class)->name('display');
    });
