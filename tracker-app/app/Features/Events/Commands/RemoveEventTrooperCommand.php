<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;

readonly class RemoveEventTrooperCommand
{
    public function __construct(public EventTrooper $event_trooper) {}
}
