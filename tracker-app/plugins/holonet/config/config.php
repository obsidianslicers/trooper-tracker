<?php

declare(strict_types=1);

return [
    // The shared list of supported trackers, read when the "Tracker list URL" setting is empty.
    'directory_url' => env(
        'HOLONET_DIRECTORY_URL',
        'https://raw.githubusercontent.com/obsidianslicers/trooper-tracker/main/tracker-app/plugins/holonet/trackers.json'
    ),

    // Seconds to wait for another tracker before giving up.
    'timeout' => (int) env('HOLONET_TIMEOUT', 10),

    // Most troops accepted from one tracker in one refresh.
    'max_troops' => 10000,

    'limits' => [
        // Requests another tracker may make here, per minute.
        'token_per_ip' => (int) env('HOLONET_LIMIT_TOKEN_PER_IP', 30),
        'history_per_key' => (int) env('HOLONET_LIMIT_HISTORY_PER_KEY', 60),
        'history_per_ip' => (int) env('HOLONET_LIMIT_HISTORY_PER_IP', 300),
        'revoke_per_ip' => (int) env('HOLONET_LIMIT_REVOKE_PER_IP', 30),
        'instance_per_ip' => (int) env('HOLONET_LIMIT_INSTANCE_PER_IP', 60),

        // Minutes between a trooper's refreshes of one link.
        'refresh_cooldown_minutes' => (int) env('HOLONET_REFRESH_COOLDOWN', 5),

        // Links a trooper may start, or allow, per hour.
        'links_per_hour' => (int) env('HOLONET_LIMIT_LINKS_PER_HOUR', 10),

        // Most seconds the nightly refresh waits before it starts, so trackers spread out.
        'nightly_jitter_seconds' => (int) env('HOLONET_NIGHTLY_JITTER', 3600),
    ],
];
