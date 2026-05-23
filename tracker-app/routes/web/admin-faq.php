<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Faq\CreateController;
use App\Http\Controllers\Admin\Faq\CreateSubmitController;
use App\Http\Controllers\Admin\Faq\DeleteSubmitController;
use App\Http\Controllers\Admin\Faq\ListController;
use App\Http\Controllers\Admin\Faq\ReorderSubmitController;
use App\Http\Controllers\Admin\Faq\UpdateController;
use App\Http\Controllers\Admin\Faq\UpdateSubmitController;
use App\Http\Controllers\Admin\FaqSections\CreateController as SectionCreateController;
use App\Http\Controllers\Admin\FaqSections\CreateSubmitController as SectionCreateSubmitController;
use App\Http\Controllers\Admin\FaqSections\DeleteSubmitController as SectionDeleteSubmitController;
use App\Http\Controllers\Admin\FaqSections\ListController as SectionListController;
use App\Http\Controllers\Admin\FaqSections\ReorderSubmitController as SectionReorderSubmitController;
use App\Http\Controllers\Admin\FaqSections\UpdateController as SectionUpdateController;
use App\Http\Controllers\Admin\FaqSections\UpdateSubmitController as SectionUpdateSubmitController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/faq')
    ->name('admin.faq.')
    ->middleware(['auth', 'check.role:administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/create', CreateSubmitController::class);
        Route::post('/reorder', ReorderSubmitController::class)->name('reorder');
        Route::get('/{faq}/update', UpdateController::class)->name('update');
        Route::post('/{faq}/update', UpdateSubmitController::class);
        Route::post('/{faq}/delete', DeleteSubmitController::class)->name('delete');

        Route::prefix('sections')
            ->name('sections.')
            ->group(function ()
            {
                Route::get('/', SectionListController::class)->name('list');
                Route::get('/create', SectionCreateController::class)->name('create');
                Route::post('/create', SectionCreateSubmitController::class);
                Route::post('/reorder', SectionReorderSubmitController::class)->name('reorder');
                Route::get('/{section}/update', SectionUpdateController::class)->name('update');
                Route::post('/{section}/update', SectionUpdateSubmitController::class);
                Route::post('/{section}/delete', SectionDeleteSubmitController::class)->name('delete');
            });
    });
