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
];
