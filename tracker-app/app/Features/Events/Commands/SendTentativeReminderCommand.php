<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;

readonly class SendTentativeReminderCommand
{
    public function __construct(public readonly EventTrooper $event_trooper) {}
}
