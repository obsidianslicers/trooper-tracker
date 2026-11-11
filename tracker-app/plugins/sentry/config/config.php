<?php

declare(strict_types=1);

return [
    // Each is used when nothing has been saved on the Admin > Plugins page.
    'dsn' => env('SENTRY_DSN'),
    'browser_dsn' => env('SENTRY_BROWSER_DSN'),
    'environment' => env('SENTRY_ENVIRONMENT'),
];
