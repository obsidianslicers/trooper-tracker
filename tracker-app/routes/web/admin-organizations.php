<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Organizations\Commands\StoreOrganizationController;
use App\Http\Controllers\Admin\Organizations\Commands\UpdateOrganizationController;
use App\Http\Controllers\Admin\Organizations\Commands\UpdateOrganizationImageController;
use App\Http\Controllers\Admin\Organizations\Pages\CreateOrganizationController;
use App\Http\Controllers\Admin\Organizations\Pages\EditOrganizationController;
use App\Http\Controllers\Admin\Organizations\Pages\IndexOrganizationCostumesController;
use App\Http\Controllers\Admin\Organizations\Pages\IndexOrganizationsController;
use Illuminate\Support\Facades\Route;

//  ADMIN/ORGANIZATIONS
Route::prefix('admin/organizations')
    ->name('admin.organizations.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', IndexOrganizationsController::class)->name('index');
        Route::get('/{parent}/children/create', CreateOrganizationController::class)->name('create');
        Route::post('/{parent}/children', StoreOrganizationController::class)->name('store');
        Route::get('/{organization}/edit', EditOrganizationController::class)->name('edit');
        Route::post('/{organization}', UpdateOrganizationController::class)->name('update');
        Route::post('/{organization}/image', UpdateOrganizationImageController::class)->name('image.update');
        Route::get('/{organization}/costumes', IndexOrganizationCostumesController::class)->name('costumes.index');
    });
