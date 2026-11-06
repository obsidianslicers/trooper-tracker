<?php

declare(strict_types=1);

return [
    // The shared list of supported trackers, read when the "Tracker list URL" setting is empty.
    'directory_url' => env(
        'FEDERATION_DIRECTORY_URL',
        'https://raw.githubusercontent.com/obsidianslicers/trooper-tracker/main/tracker-app/plugins/federation/trackers.json'
    ),

    // Seconds to wait for another tracker before giving up.
    'timeout' => (int) env('FEDERATION_TIMEOUT', 10),

    // Most troops accepted from one tracker in one refresh.
    'max_troops' => 10000,
];
