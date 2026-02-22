<?php

declare(strict_types=1);

return [
    // Primary webhook url for event notifications. Can be overridden per-environment.
    'webhook_url' => env('DISCORD_WEBHOOK_URL', null),

    // Optional named webhooks if you need multiple channels
    'webhooks' => [
        'default' => env('DISCORD_WEBHOOK_URL', null),
    ],

    // Mapping of squad identifiers -> Discord role mention string.
    'squad_roles' => [
        // string keys allow resolving by organization name
        'Florida Garrison' => '<@&948046239956627506>',
        'Everglades Squad' => '<@&914344158678900766>',
        'Makaze Squad' => '<@&914343663474200597>',
        'Parjai Squad' => '<@&914344264253718568>',
        'Squad 7' => '<@&914344334776737822>',
        'Tampa Bay Squad' => '<@&914344438472527912>',
        // keep mapping for any additional display/key variants you'd like
    ],

    // Default mention if squad not found
    'default_mention' => 'Florida Garrison',
];
