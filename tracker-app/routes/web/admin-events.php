<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Events\AddEventTrooperController;
use App\Http\Controllers\Admin\Events\AddEventTrooperCostumePickerController;
use App\Http\Controllers\Admin\Events\CopyController;
use App\Http\Controllers\Admin\Events\CopySubmitController;
use App\Http\Controllers\Admin\Events\CreateController;
use App\Http\Controllers\Admin\Events\CreateSubmitController;
use App\Http\Controllers\Admin\Events\DeleteUploadController;
use App\Http\Controllers\Admin\Events\GetEventTrooperOrgOptionsController;
use App\Http\Controllers\Admin\Events\ListController;
use App\Http\Controllers\Admin\Events\MissionReviewController;
use App\Http\Controllers\Admin\Events\RemoveEventTrooperController;
use App\Http\Controllers\Admin\Events\UpdateCharityController;
use App\Http\Controllers\Admin\Events\UpdateCharitySubmitController;
use App\Http\Controllers\Admin\Events\UpdateController;
use App\Http\Controllers\Admin\Events\UpdateShiftsController;
use App\Http\Controllers\Admin\Events\UpdateShiftsSubmitController;
use App\Http\Controllers\Admin\Events\UpdateSubmitController;
use App\Http\Controllers\Admin\Events\UpdateTroopersController;
use App\Http\Controllers\Admin\Events\UpdateTroopersSubmitController;
use App\Http\Controllers\Admin\Events\UploadImageController;
use App\Http\Controllers\Admin\Events\UploadsController;
use Illuminate\Support\Facades\Route;

//  ADMIN/EVENTS
Route::prefix('admin/events')
    ->name('admin.events.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function () {
        Route::get('/', ListController::class)->name('list');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/create', CreateSubmitController::class);
        Route::get('/{event}/copy', CopyController::class)->name('copy');
        Route::post('/{event}/copy', CopySubmitController::class);
        Route::get('/{event}/update', UpdateController::class)->name('update');
        Route::post('/{event}/update', UpdateSubmitController::class);
        Route::get('/{event}/shifts', UpdateShiftsController::class)->name('shifts');
        Route::post('/{event}/shifts', UpdateShiftsSubmitController::class);
        Route::get('/{event}/charity', UpdateCharityController::class)->name('charity');
        Route::post('/{event}/charity', UpdateCharitySubmitController::class);
        Route::get('/{event}/troopers', UpdateTroopersController::class)->name('troopers');
        Route::post('/{event}/troopers', UpdateTroopersSubmitController::class);
        Route::get('/{event}/troopers/{event_trooper}/org-options', GetEventTrooperOrgOptionsController::class)->name('troopers.org-options');
        Route::get('/{event}/shifts/{event_shift}/troopers/costume-picker', AddEventTrooperCostumePickerController::class)->name('troopers.costume-picker');
        Route::post('/{event}/shifts/{event_shift}/troopers/add', AddEventTrooperController::class)->name('troopers.add');
        Route::post('/{event}/troopers/{event_trooper}/remove', RemoveEventTrooperController::class)->name('troopers.remove');
        Route::get('/{event}/uploads', UploadsController::class)->name('uploads');
        Route::post('/{event}/upload', UploadImageController::class)->name('upload-image');
        Route::get('/{event}/mission-review', MissionReviewController::class)->name('mission-review');
        Route::post('/{event}/uploads/{event_upload}/delete', DeleteUploadController::class)->name('uploads.delete');
    });
