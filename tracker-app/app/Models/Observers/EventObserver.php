<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Models\Event;
use App\Models\Organization;
use App\Services\GeocodingService;
use App\Services\GoogleService;
use Throwable;

/**
 * Handles lifecycle events for the Event model.
 *
 * Observes Event model changes and performs related actions such as geocoding,
 * and Discord notifications when events are created or updated.
 */
class EventObserver
{
    /**
     * Handle the Event "creating" event.
     *
     * Sets the primary_organization_id based on the event's organization's primary club.
     *
     * @param Event $event The event instance being created.
     * @return void
     */
    public function creating(Event $event): void
    {
        if ($event->organization_id !== null)
        {
            $organization = Organization::findOrFail($event->organization_id);

            $event->primary_organization_id = $organization->getPrimaryClub()->id;
        }
    }

    /**
     * Handle the Event "created" event.
     *
     * @param Event $event The event instance that was created.
     */
    public function created(Event $event): void
    {
        // Geocode the event location after creation to ensure we have an ID for any related data.
        $this->storeGeocode($event);
    }

    /**
     * Handle the Event "updated" event.
     *
     * Re-geocodes the event location if any address components have changed.
     *
     * @param Event $event The event instance that was updated.
     * @return void
     */
    public function updated(Event $event): void
    {
        $attributes = ['venue_address', 'venue_city', 'venue_state', 'venue_zip', 'venue_country'];

        if ($event->isDirty($attributes))
        {
            $this->storeGeocode($event);
        }
    }

    /**
     * Fetch and store geocoding coordinates for an event.
     *
     * Uses the Google Maps API if configured, otherwise falls back to Nominatim geocoding.
     * Silently handles failures by reporting exceptions without stopping execution.
     *
     * @param Event $event The event instance to geocode.
     * @return void
     */
    private function storeGeocode(Event $event): void
    {
        try
        {
            $address = $this->buildGeocodeAddress($event);

            if (config('services.google.maps_api_key'))
            {
                $google = app(GoogleService::class);

                [$latitude, $longitude] = $google->getLatitudeLongitude($address);

                $event->latitude = $latitude;
                $event->longitude = $longitude;
            }
            else
            {
                $geocoding = app(GeocodingService::class);

                [$latitude, $longitude] = $geocoding->getLatitudeLongitude($address);

                $event->latitude = $latitude;
                $event->longitude = $longitude;
            }
        }
        catch (Throwable $e)
        {
            report($e);
        }
    }

    /**
     * Build a comma-separated geocoding address string from event venue fields.
     *
     * Constructs an address by combining venue address, city, state, zip, and country fields.
     * Avoids duplicating address components that are already included in the base address.
     *
     * @param Event $event The event instance to build the address from.
     * @return string The formatted address string suitable for geocoding services.
     */
    private function buildGeocodeAddress(Event $event): string
    {
        $parts = [];

        // Normalize the base address for duplicate detection
        $base = strtolower((string) $event->venue_address);

        // Always start with the raw address field
        $parts[] = trim($event->venue_address);

        // Append city if not already included
        if (!empty($event->venue_city) && !str_contains($base, strtolower($event->venue_city)))
        {
            $parts[] = $event->venue_city;
        }

        // Append state if not already included
        if (!empty($event->venue_state) && !str_contains($base, strtolower($event->venue_state)))
        {
            $parts[] = $event->venue_state;
        }

        // Append ZIP if not already included
        if (!empty($event->venue_zip) && !str_contains($base, strtolower($event->venue_zip)))
        {
            $parts[] = $event->venue_zip;
        }

        // Append country if not already included
        if (!empty($event->venue_country) && !str_contains($base, strtolower($event->venue_country)))
        {
            $parts[] = $event->venue_country;
        }

        // Join with commas for Nominatim
        return implode(', ', array_filter($parts));
    }
}
