<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;

readonly class SignUpEventTrooperCommand
{
    public function __construct(
        public readonly EventShift $event_shift,
        public readonly Trooper $trooper,
        public readonly Trooper $added_by_trooper,
    ) {
    }
}
