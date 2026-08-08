<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Facades\TroopTrackerFacade;
use App\Jobs\DeleteEventForumThreadJob;
use App\Jobs\SendEventUpdatedNotificationsJob;
use App\Jobs\UpdateEventForumThreadJob;
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
     * @param  Event  $event  The event instance being created.
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
     * @param  Event  $event  The event instance that was created.
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
     * @param  Event  $event  The event instance that was updated.
     */
    public function updated(Event $event): void
    {
        $attributes = [Event::VENUE_ADDRESS];

        if ($event->wasChanged($attributes))
        {
            $this->storeGeocode($event);
        }

        if ($this->shouldQueueForumThreadSync($event))
        {
            dispatch(new UpdateEventForumThreadJob($event->getKey()));
        }

        $watch_fields = [
            Event::NAME,
            Event::EVENT_START,
            Event::EVENT_END,
            Event::VENUE,
            Event::VENUE_ADDRESS,
            Event::COMMENTS,
        ];

        if ($event->wasChanged($watch_fields))
        {
            $changed = array_values(array_intersect($watch_fields, array_keys($event->getChanges())));
            dispatch(new SendEventUpdatedNotificationsJob($event, $changed));
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
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        if (!$event->create_forum_thread || empty($event->thread_id))
        {
            return;
        }

        dispatch(new DeleteEventForumThreadJob((int) $event->id, (int) $event->thread_id))->afterCommit();
    }

    /**
     * Fetch and store geocoding coordinates for an event.
     *
     * Uses the Google Maps API if configured, otherwise falls back to Nominatim geocoding.
     * Silently handles failures by reporting exceptions without stopping execution.
     *
     * @param  Event  $event  The event instance to geocode.
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
            $address = $event->venue_address ?? '';

            if (empty(trim($address)))
            {
                return;
            }

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
