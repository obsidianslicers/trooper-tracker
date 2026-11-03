<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\XenforoWebhookController;
use App\Http\Middleware\CheckVisitorAccessMiddleware;
use App\Http\Middleware\TrooperSetupRequiredMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/xenforo', XenforoWebhookController::class)
    ->withoutMiddleware([
        TrooperSetupRequiredMiddleware::class,
        CheckVisitorAccessMiddleware::class,
    ])
    ->name('webhooks.xenforo');
