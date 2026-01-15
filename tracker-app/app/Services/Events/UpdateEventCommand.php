<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Event;

/**
 * Service for updating an event's core properties.
 *
 * This service handles updating event details including name, status,
 * attendance limits, location coordinates, contact information, venue details,
 * event timing, request specifics, venue amenities, and miscellaneous fields.
 */
class UpdateEventCommand
{
    /**
     * Update an event.
     *
     * Updates the properties of the given event based on the provided data.
     * Saves the changes to the database.
     *
     * @param Event $event The event to update
     * @param array $data The data to update the event with
     * @return void
     */
    public function __invoke(Event $event, array $data): void
    {
        $event->fill($data);

        $event->save();
    }
}