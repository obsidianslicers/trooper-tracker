<?php

declare(strict_types=1);

// Fallbacks for values not saved on the plugin's settings page.
return [
    'client_id' => env('XENFORO_CLIENT_ID'),
    'client_secret' => env('XENFORO_CLIENT_SECRET'),
    'redirect' => env('XENFORO_REDIRECT_URI'),
    'base_url' => rtrim((string) env('XENFORO_BASE_URL', ''), '/'),
    'api_key' => env('XENFORO_API_KEY'),
    'api_user' => env('XENFORO_API_USER'),
    'name' => env('XENFORO_NAME'),
    'webhook_secret' => env('XENFORO_WEBHOOK_SECRET'),
    'require_xenforo' => (bool) env('TRACKER_REQUIRE_XENFORO', false),
    'date_in_thread_titles' => (bool) env('XENFORO_DATE_IN_THREAD_TITLES', false),
    // OAuth paths, so different XenForo setups can be supported. These default
    // to the standard XenForo 2.3 endpoints.
    'authorize_path' => env('XENFORO_AUTHORIZE_PATH', '/index.php?oauth2/authorize'),
    'token_path' => env('XENFORO_TOKEN_PATH', '/index.php?api/oauth2/token'),
    'me_path' => env('XENFORO_ME_PATH', '/api/me'),
];
