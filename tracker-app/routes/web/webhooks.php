<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\XenforoWebhookController;
use App\Http\Middleware\CheckVisitorAccessMiddleware;
use App\Http\Middleware\CheckXenforoBanMiddleware;
use App\Http\Middleware\EnsureXenforoLinked;
use App\Http\Middleware\TrooperSetupRequiredMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/xenforo', XenforoWebhookController::class)
    ->withoutMiddleware([
        EnsureXenforoLinked::class,
        TrooperSetupRequiredMiddleware::class,
        CheckXenforoBanMiddleware::class,
        CheckVisitorAccessMiddleware::class,
    ])
    ->name('webhooks.xenforo');
