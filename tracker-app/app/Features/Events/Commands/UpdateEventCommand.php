<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;

/**
 * Command to update event details.
 *
 * Updates the event's core properties including name, status, attendance limits,
 * location coordinates, contact information, venue details, event timing,
 * request specifics, venue amenities, and miscellaneous fields.
 *
 * @see UpdateEventCommandHandler
 */
readonly class UpdateEventCommand
{
    /**
     * Create a new command instance.
     *
     * @param Event $event The event to update
     * @param array<string, mixed> $data The validated data to update the event with
     */
    public function __construct(
        public Event $event,
        public array $data)
    {
    }
}
