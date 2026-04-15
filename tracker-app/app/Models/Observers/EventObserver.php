<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Facades\TroopTrackerFacade;
use App\Jobs\UpdateEventForumThreadJob;
use App\Models\Event;
use App\Models\Organization;
use App\Services\Forums\XenforoService;
use App\Services\GeocodingService;
use App\Services\GoogleService;
use Illuminate\Support\Facades\Log;
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
     * @return void
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

        if ($event->wasChanged($attributes))
        {
            $this->storeGeocode($event);
        }

        if ($this->shouldQueueForumThreadSync($event))
        {
            dispatch(new UpdateEventForumThreadJob($event->getKey()));
        }
    }

    /**
     * Handle the Event "deleted" event.
     *
     * When an event that has a linked XenForo thread is deleted, attempt to
     * delete the corresponding thread from XenForo as well to keep the forum
     * in sync.
     */
    public function deleted(Event $event): void
    {
        if (! TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        if (! $event->create_forum_thread || empty($event->thread_id))
        {
            return;
        }

        try
        {
            $xenforo = app(XenforoService::class);

            $result = $xenforo->delete_thread((int) $event->thread_id);

            if (($result['status'] ?? 0) < 200 || ($result['status'] ?? 0) >= 300)
            {
                Log::warning('Failed to delete XenForo thread for deleted event', [
                    'event' => $event->id,
                    'thread_id' => $event->thread_id,
                    'status' => $result['status'] ?? null,
                    'body' => $result['body'] ?? null,
                ]);
            }
        }
        catch (Throwable $e)
        {
            report($e);
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
        if (config('app.env') === 'testing')
        {
            //  don't attempt geocoding during tests to avoid external API calls and potential rate limits
            return;
        }

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

    private function shouldQueueForumThreadSync(Event $event): bool
    {
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return false;
        }

        if (empty($event->thread_id) || empty($event->post_id) || $event->create_forum_thread === false)
        {
            return false;
        }

        return $event->wasChanged([
            Event::NAME,
            Event::VENUE,
            Event::VENUE_ADDRESS,
            Event::EVENT_START,
            Event::EVENT_END,
            Event::EVENT_WEBSITE,
            Event::EXPECTED_ATTENDEES,
            Event::REQUESTED_NUMBER_CHARACTERS,
            Event::REQUESTED_CHARACTER_TYPES,
            Event::SECURE_STAGING_AREA,
            Event::ALLOW_BLASTERS,
            Event::ALLOW_PROPS,
            Event::PARKING_AVAILABLE,
            Event::ACCESSIBLE,
            Event::AMENITIES,
            Event::COMMENTS,
            Event::REFERRED_BY,
        ]);
    }
}
