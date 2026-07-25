<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Command to re-assign GOING / STAND_BY across an event's roster after
 * capacity limits change.
 *
 * @see ReconcileEventRosterCommandHandler
 */
readonly class ReconcileEventRosterCommand
{
    public function __construct(
        public Event $event,
        public Trooper $changed_by,
    ) {}
}
