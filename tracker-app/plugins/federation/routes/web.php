<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TroopTracker\Plugins\Federation\Http\Controllers\ApproveTrackerLinkController;
use TroopTracker\Plugins\Federation\Http\Controllers\FinishTrackerLinkController;
use TroopTracker\Plugins\Federation\Http\Controllers\RefreshTrackerLinkController;
use TroopTracker\Plugins\Federation\Http\Controllers\RemoveTrackerLinkController;
use TroopTracker\Plugins\Federation\Http\Controllers\RevokeTrackerAccessController;
use TroopTracker\Plugins\Federation\Http\Controllers\ShowAuthorizeLinkController;
use TroopTracker\Plugins\Federation\Http\Controllers\ShowLinkedTrackersController;
use TroopTracker\Plugins\Federation\Http\Controllers\StartTrackerLinkController;

// Served under /plugins/federation, named plugins.federation.*, for signed-in active troopers.

Route::get('/', ShowLinkedTrackersController::class)->name('index');
Route::post('/links', StartTrackerLinkController::class)->name('links.start');
Route::get('/callback', FinishTrackerLinkController::class)->name('callback');
Route::post('/links/{link}/refresh', RefreshTrackerLinkController::class)
    ->whereNumber('link')
    ->name('links.refresh');
Route::delete('/links/{link}', RemoveTrackerLinkController::class)
    ->whereNumber('link')
    ->name('links.remove');

Route::get('/authorize', ShowAuthorizeLinkController::class)->name('authorize');
Route::post('/authorize', ApproveTrackerLinkController::class)->name('authorize.submit');
Route::delete('/grants/{grant}', RevokeTrackerAccessController::class)
    ->whereNumber('grant')
    ->name('grants.revoke');
