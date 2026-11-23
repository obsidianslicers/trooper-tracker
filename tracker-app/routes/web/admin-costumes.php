<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Costumes\Commands\DestroyCostumeController;
use App\Http\Controllers\Admin\Costumes\Commands\MergeCostumesController;
use App\Http\Controllers\Admin\Costumes\Commands\StoreCostumeController;
use App\Http\Controllers\Admin\Costumes\Commands\UpdateCostumeController;
use App\Http\Controllers\Admin\Costumes\Pages\CreateCostumeController;
use App\Http\Controllers\Admin\Costumes\Pages\DeleteCostumeController;
use App\Http\Controllers\Admin\Costumes\Pages\EditCostumeController;
use App\Http\Controllers\Admin\Costumes\Pages\IndexCostumesController;
use App\Http\Controllers\Admin\Costumes\Pages\ShowMergeCostumesFormController;
use Illuminate\Support\Facades\Route;

//  ADMIN/COSTUMES
Route::prefix('admin/costumes')
    ->name('admin.costumes.')
    ->middleware(['auth', 'check.role:staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', IndexCostumesController::class)->name('index');
        Route::get('/create', CreateCostumeController::class)->name('create');
        Route::post('/', StoreCostumeController::class)->name('store');
        Route::middleware('check.role:system_owner')
            ->group(function ()
            {
                Route::get('/merge', ShowMergeCostumesFormController::class)->name('merge.create');
                Route::post('/merge', MergeCostumesController::class)->name('merge.store');
            });
        Route::get('/{costume}/edit', EditCostumeController::class)->name('edit');
        Route::post('/{costume}', UpdateCostumeController::class)->name('update');
        Route::get('/{costume}/delete', DeleteCostumeController::class)->name('delete');
        Route::post('/{costume}/delete', DestroyCostumeController::class)->name('destroy');
    });
