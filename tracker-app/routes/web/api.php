<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PublicApiController;
use Illuminate\Support\Facades\Route;

Route::get('/api', PublicApiController::class)->name('api.public');
