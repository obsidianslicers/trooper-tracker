<?php

declare(strict_types=1);

use App\Http\Controllers\ServiceRecords\AchievementsController;
use App\Http\Controllers\ServiceRecords\AwardsController;
use App\Http\Controllers\ServiceRecords\CommandStaffController;
use App\Http\Controllers\ServiceRecords\LeaderboardController;
use App\Http\Controllers\ServiceRecords\PhotoGalleryController;
use App\Http\Controllers\ServiceRecords\TrooperController;
use Illuminate\Support\Facades\Route;

//  SERVICE RECORD
Route::prefix('service-records')
    ->name('service-records.')
    ->middleware(['auth', 'verified', 'check.active'])
    ->group(function ()
    {
        Route::get('/trooper/{trooper}', TrooperController::class)->name('trooper');
        Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard');
        Route::get('/awards', AwardsController::class)->name('awards');
        Route::get('/achievements', AchievementsController::class)->name('achievements');
        Route::get('/command-staff', CommandStaffController::class)->name('command-staff');
        Route::get('/photo-gallery', PhotoGalleryController::class)->name('photo-gallery');
    });