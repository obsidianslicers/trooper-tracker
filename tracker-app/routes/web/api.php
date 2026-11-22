<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Pages\ShowPublicApiController;
use Illuminate\Support\Facades\Route;

Route::get('/api', ShowPublicApiController::class)->name('api.public');
