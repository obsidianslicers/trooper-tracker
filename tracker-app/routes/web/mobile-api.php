<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/mobile-api', MobileApiController::class)->name('api.mobile');
