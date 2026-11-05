<?php

declare(strict_types=1);

return [
    // Used when no webhook URL has been saved on the Admin > Plugins page.
    'webhook_url' => env('DISCORD_WEBHOOK_URL'),
];
