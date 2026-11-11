<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TroopTracker\Plugins\Xenforo\Http\Controllers\SaveEventTypeForumController;

Route::post('/event-types/{event_type}/forum', SaveEventTypeForumController::class)
    ->name('event-types.forum');
