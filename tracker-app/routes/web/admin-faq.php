<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Faq\Commands\DestroyFaqItemController;
use App\Http\Controllers\Admin\Faq\Commands\DestroyFaqSectionController;
use App\Http\Controllers\Admin\Faq\Commands\ReorderFaqItemsController;
use App\Http\Controllers\Admin\Faq\Commands\ReorderFaqSectionsController;
use App\Http\Controllers\Admin\Faq\Commands\StoreFaqItemController;
use App\Http\Controllers\Admin\Faq\Commands\StoreFaqSectionController;
use App\Http\Controllers\Admin\Faq\Commands\UpdateFaqItemController;
use App\Http\Controllers\Admin\Faq\Commands\UpdateFaqSectionController;
use App\Http\Controllers\Admin\Faq\Pages\CreateFaqItemController;
use App\Http\Controllers\Admin\Faq\Pages\CreateFaqSectionController;
use App\Http\Controllers\Admin\Faq\Pages\EditFaqItemController;
use App\Http\Controllers\Admin\Faq\Pages\EditFaqSectionController;
use App\Http\Controllers\Admin\Faq\Pages\IndexFaqController;
use Illuminate\Support\Facades\Route;

//  ADMIN/FAQ
Route::prefix('admin/faq')
    ->name('admin.faq.')
    ->middleware(['auth', 'check.role:staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', IndexFaqController::class)->name('index');

        Route::prefix('sections')
            ->name('sections.')
            ->group(function ()
            {
                Route::get('/create', CreateFaqSectionController::class)->name('create');
                Route::post('/', StoreFaqSectionController::class)->name('store');
                Route::post('/reorder', ReorderFaqSectionsController::class)->name('reorder');
                Route::get('/{section}/edit', EditFaqSectionController::class)->name('edit');
                Route::post('/{section}', UpdateFaqSectionController::class)->name('update');
                Route::post('/{section}/delete', DestroyFaqSectionController::class)->name('destroy');
            });

        Route::prefix('items')
            ->name('items.')
            ->group(function ()
            {
                Route::get('/create', CreateFaqItemController::class)->name('create');
                Route::post('/', StoreFaqItemController::class)->name('store');
                Route::post('/reorder', ReorderFaqItemsController::class)->name('reorder');
                Route::get('/{item}/edit', EditFaqItemController::class)->name('edit');
                Route::post('/{item}', UpdateFaqItemController::class)->name('update');
                Route::post('/{item}/delete', DestroyFaqItemController::class)->name('destroy');
            });
    });
