<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Faq\CreateController;
use App\Http\Controllers\Admin\Faq\CreateSubmitController;
use App\Http\Controllers\Admin\Faq\DeleteSubmitController;
use App\Http\Controllers\Admin\Faq\ListController;
use App\Http\Controllers\Admin\Faq\UpdateController;
use App\Http\Controllers\Admin\Faq\UpdateSubmitController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/faq')
    ->name('admin.faq.')
    ->middleware(['auth', 'check.role:administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/create', CreateSubmitController::class);
        Route::get('/{faq}/update', UpdateController::class)->name('update');
        Route::post('/{faq}/update', UpdateSubmitController::class);
        Route::post('/{faq}/delete', DeleteSubmitController::class)->name('delete');
    });
