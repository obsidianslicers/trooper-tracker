<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Organizations\CostumesController;
use App\Http\Controllers\Admin\Organizations\CostumesSubmitController;
use App\Http\Controllers\Admin\Organizations\CreateController;
use App\Http\Controllers\Admin\Organizations\CreateCostumeController;
use App\Http\Controllers\Admin\Organizations\CreateCostumeSubmitController;
use App\Http\Controllers\Admin\Organizations\CreateSubmitController;
use App\Http\Controllers\Admin\Organizations\ListController;
use App\Http\Controllers\Admin\Organizations\UpdateController;
use App\Http\Controllers\Admin\Organizations\UpdateImageController;
use App\Http\Controllers\Admin\Organizations\UpdateSubmitController;
use Illuminate\Support\Facades\Route;

//  ADMIN/ORGANIZATIONS
Route::prefix('admin/organizations')
    ->name('admin.organizations.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/{parent}/create', CreateController::class)->name('create');
        Route::post('/{parent}/create', CreateSubmitController::class);
        Route::get('/{organization}/update', UpdateController::class)->name('update');
        Route::post('/{organization}/update', UpdateSubmitController::class);
        Route::post('/{organization}/image', UpdateImageController::class)->name('update-image');
        Route::get('/{organization}/costumes', CostumesController::class)->name('costumes');
        Route::post('/{organization}/costumes', CostumesSubmitController::class);
        Route::get('/{organization}/costumes/create', CreateCostumeController::class)->name('create-costume');
        Route::post('/{organization}/costumes/create', CreateCostumeSubmitController::class);
    });
