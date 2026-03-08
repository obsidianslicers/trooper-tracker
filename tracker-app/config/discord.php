<?php

declare(strict_types=1);

return [
    // Primary webhook url for event notifications. Can be overridden per-environment.
    'webhook_url' => env('DISCORD_WEBHOOK_URL', null),

    // Optional named webhooks if you need multiple channels
    'webhooks' => [
        'default' => env('DISCORD_WEBHOOK_URL', null),
    ],
];
