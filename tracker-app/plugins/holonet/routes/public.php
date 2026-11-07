<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TroopTracker\Plugins\Holonet\Http\Controllers\ShowTrackerCardController;

// Served under /plugins/holonet, named plugins.holonet.*, for anyone (other trackers).

Route::get('/instance', ShowTrackerCardController::class)->name('instance');
