<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;

/**
 * Command to update an event trooper's sign-up information.
 *
 * Updates an existing EventTrooper record with validated data,
 * such as status changes, costume selections, or other attributes.
 *
 * @see UpdateEventTrooperCommandHandler
 */
readonly class UpdateEventTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param EventTrooper $event_trooper The event trooper record to update.
     * @param array<string, mixed> $valid_data Validated attributes to update.
     */
    public function __construct(
        public EventTrooper $event_trooper,
        public array $valid_data,
    ) {
    }
}

