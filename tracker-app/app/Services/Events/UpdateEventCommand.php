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
        $event->name = $data['name'];
        $event->status = $data['status'];
        $event->troopers_allowed = $data['troopers_allowed'] ?? null;
        $event->handlers_allowed = $data['handlers_allowed'] ?? null;
        $event->friends_allowed = $data['friends_allowed'] ?? null;
        $event->tentative_signups_allowed = $data['tentative_signups_allowed'] ?? false;

        // Coordinates
        $event->latitude = $data['latitude'] ?? null;
        $event->longitude = $data['longitude'] ?? null;
        // Contact info
        $event->contact_name = $data['contact_name'] ?? null;
        $event->contact_phone = $data['contact_phone'] ?? null;
        $event->contact_email = $data['contact_email'] ?? null;
        // Event details
        $event->venue = $data['venue'] ?? null;
        $event->venue_address = $data['venue_address'] ?? null;
        $event->venue_city = $data['venue_city'] ?? null;
        $event->venue_state = $data['venue_state'] ?? null;
        $event->venue_zip = $data['venue_zip'] ?? null;
        $event->venue_country = $data['venue_country'] ?? null;
        $event->event_start = $data['event_start'];
        $event->event_end = $data['event_end'];
        $event->event_website = $data['event_website'] ?? null;

        // Request specifics
        $event->expected_attendees = $data['expected_attendees'] ?? null;
        $event->requested_number_characters = $data['requested_number_characters'] ?? null;
        $event->requested_character_types = $data['requested_character_types'] ?? null;

        // Venue amenities / permissions
        $event->secure_staging_area = $data['secure_staging_area'] ?? false;
        $event->allow_blasters = $data['allow_blasters'] ?? false;
        $event->allow_props = $data['allow_props'] ?? false;
        $event->parking_available = $data['parking_available'] ?? false;
        $event->accessible = $data['accessible'] ?? false;
        $event->amenities = $data['amenities'] ?? null;

        // Misc
        $event->comments = $data['comments'] ?? null;
        $event->referred_by = $data['referred_by'] ?? null;
        $event->source = $data['source'] ?? $event->source;

        $event->save();
    }
}