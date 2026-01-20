<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;

/**
 * Command to update trooper profile information.
 *
 * Updates the trooper model with validated data and optionally marks
 * the trooper's initial setup as completed by setting setup_completed_at.
 *
 * @see UpdateEventTrooperCommandHandler
 */
readonly class UpdateEventTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param EventTrooper $event_trooper The event trooper to update
     * @param array<string, mixed> $valid_data Validated attributes to update on the event trooper
     */
    public function __construct(
        public EventTrooper $event_trooper,
        public array $valid_data,
    ) {
    }
}

