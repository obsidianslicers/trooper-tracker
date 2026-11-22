<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Notices\Commands\StoreNoticeController;
use App\Http\Controllers\Admin\Notices\Commands\UpdateNoticeController;
use App\Http\Controllers\Admin\Notices\Pages\CreateNoticeController;
use App\Http\Controllers\Admin\Notices\Pages\EditNoticeController;
use App\Http\Controllers\Admin\Notices\Pages\IndexNoticesController;
use Illuminate\Support\Facades\Route;

//  ADMIN/NOTICES
Route::prefix('admin/notices')
    ->name('admin.notices.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', IndexNoticesController::class)->name('index');
        Route::get('/create', CreateNoticeController::class)->name('create');
        Route::post('/', StoreNoticeController::class)->name('store');
        Route::get('/{notice}/edit', EditNoticeController::class)->name('edit');
        Route::post('/{notice}', UpdateNoticeController::class)->name('update');
    });
