<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TroopTracker\Plugins\Holonet\Http\Controllers\ApproveTrackerLinkController;
use TroopTracker\Plugins\Holonet\Http\Controllers\FinishTrackerLinkController;
use TroopTracker\Plugins\Holonet\Http\Controllers\RefreshTrackerLinkController;
use TroopTracker\Plugins\Holonet\Http\Controllers\RemoveTrackerLinkController;
use TroopTracker\Plugins\Holonet\Http\Controllers\RevokeTrackerAccessController;
use TroopTracker\Plugins\Holonet\Http\Controllers\ShowAuthorizeLinkController;
use TroopTracker\Plugins\Holonet\Http\Controllers\ShowLinkedTrackersController;
use TroopTracker\Plugins\Holonet\Http\Controllers\StartTrackerLinkController;

// Served under /plugins/holonet, named plugins.holonet.*, for signed-in active troopers.

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
