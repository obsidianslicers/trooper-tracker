<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Troopers\ApprovalListController;
use App\Http\Controllers\Admin\Troopers\ApprovalSubmitHtmxController;
use App\Http\Controllers\Admin\Troopers\AuthorityController;
use App\Http\Controllers\Admin\Troopers\AuthoritySubmitController;
use App\Http\Controllers\Admin\Troopers\DenialSubmitHtmxController;
use App\Http\Controllers\Admin\Troopers\ListController;
use App\Http\Controllers\Admin\Troopers\ProfileController;
use App\Http\Controllers\Admin\Troopers\ProfileSubmitController;
use Illuminate\Support\Facades\Route;


//  ADMIN/TROOPERs
Route::prefix('admin/troopers')
    ->name('admin.troopers.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/approvals', ApprovalListController::class)->name('approvals');
        Route::post('/approvals/{trooper}/approve', ApprovalSubmitHtmxController::class)->name('approve-htmx');
        Route::post('/approvals/{trooper}/deny', DenialSubmitHtmxController::class)->name('deny-htmx');

        Route::get('/{trooper}', ProfileController::class)->name('profile');
        Route::post('/{trooper}', ProfileSubmitController::class);
        Route::get('/{trooper}/authority', AuthorityController::class)->name('authority');
        Route::post('/{trooper}/authority', AuthoritySubmitController::class);
    });
