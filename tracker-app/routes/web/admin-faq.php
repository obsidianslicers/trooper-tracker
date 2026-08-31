<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Faq\CreateItemController;
use App\Http\Controllers\Admin\Faq\CreateItemSubmitController;
use App\Http\Controllers\Admin\Faq\DeleteItemSubmitController;
use App\Http\Controllers\Admin\Faq\ListItemsController;
use App\Http\Controllers\Admin\Faq\ReorderItemsSubmitController;
use App\Http\Controllers\Admin\Faq\UpdateItemController;
use App\Http\Controllers\Admin\Faq\UpdateItemSubmitController;
use App\Http\Controllers\Admin\Faq\CreateSectionController;
use App\Http\Controllers\Admin\Faq\CreateSectionSubmitController;
use App\Http\Controllers\Admin\Faq\DeleteSectionSubmitController;
use App\Http\Controllers\Admin\Faq\ListSectionsController;
use App\Http\Controllers\Admin\Faq\ReorderSectionsSubmitController;
use App\Http\Controllers\Admin\Faq\UpdateSectionController;
use App\Http\Controllers\Admin\Faq\UpdateSectionSubmitController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/faq')
    ->name('admin.faq.')
    ->middleware(['auth', 'check.role:administrator'])
    ->group(function ()
    {
        Route::get('/', ListItemsController::class)->name('list');
        Route::get('/create', CreateItemController::class)->name('create');
        Route::post('/create', CreateItemSubmitController::class);
        Route::post('/reorder', ReorderItemsSubmitController::class)->name('reorder');
        Route::get('/{faq}/update', UpdateItemController::class)->name('update');
        Route::post('/{faq}/update', UpdateItemSubmitController::class);
        Route::post('/{faq}/delete', DeleteItemSubmitController::class)->name('delete');

        Route::prefix('sections')
            ->name('sections.')
            ->group(function ()
            {
                Route::get('/', ListSectionsController::class)->name('list');
                Route::get('/create', CreateSectionController::class)->name('create');
                Route::post('/create', CreateSectionSubmitController::class);
                Route::post('/reorder', ReorderSectionsSubmitController::class)->name('reorder');
                Route::get('/{section}/update', UpdateSectionController::class)->name('update');
                Route::post('/{section}/update', UpdateSectionSubmitController::class);
                Route::post('/{section}/delete', DeleteSectionSubmitController::class)->name('delete');
            });
    });
