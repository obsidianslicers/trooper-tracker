<?php

declare(strict_types=1);

use App\Http\Controllers\ServiceRecords\Pages\ShowAchievementsController;
use App\Http\Controllers\ServiceRecords\Pages\ShowAwardsController;
use App\Http\Controllers\ServiceRecords\Pages\ShowCommandStaffController;
use App\Http\Controllers\ServiceRecords\Pages\ShowCostumeLeaderboardController;
use App\Http\Controllers\ServiceRecords\Pages\ShowLeaderboardController;
use App\Http\Controllers\ServiceRecords\Pages\ShowPhotoGalleryController;
use App\Http\Controllers\ServiceRecords\Pages\ShowTrooperServiceRecordController;
use Illuminate\Support\Facades\Route;

//  SERVICE RECORD
Route::prefix('service-records')
    ->name('service-records.')
    ->middleware(['auth', 'verified', 'check.active'])
    ->group(function ()
    {
        Route::get('/trooper/{trooper}', ShowTrooperServiceRecordController::class)->name('trooper');
        Route::get('/leaderboard', ShowLeaderboardController::class)->name('leaderboard');
        Route::get('/awards', ShowAwardsController::class)->name('awards');
        Route::get('/achievements', ShowAchievementsController::class)->name('achievements');
        Route::get('/command-staff', ShowCommandStaffController::class)->name('command-staff');
        Route::get('/photo-gallery', ShowPhotoGalleryController::class)->name('photo-gallery');
        Route::get('/costumes/{costume}', ShowCostumeLeaderboardController::class)->name('costume');
    });
