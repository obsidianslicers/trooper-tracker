<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Models\Event;
use App\Models\Organization;
use App\Services\GeocodingService;
use App\Services\GoogleService;
use App\Jobs\SendDiscordEventNotification;
use Throwable;

/**
 * Handles lifecycle events for the Event model.
 */
class EventObserver
{
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

        // Determine organization name (prefer organization, then primary_organization)
        $orgName = null;
        try {
            $orgName = $event->organization?->name ?? $event->primary_organization?->name ?? null;
        } catch (\Throwable $e) {
            $orgName = null;
        }

        // Dispatch a job to notify Discord when a new event is created.
        // Pass the organization name (or null) — DiscordNotifier will resolve to a role if configured.
        SendDiscordEventNotification::dispatch(
            $event->id,
            $event->name,
            $event->comments ?? null,
            $orgName
        );
    }

    /**
     * Handle the Event "updated" event.
     *
     * @param Event $event The event instance that was updated.
     */
    public function updated(Event $event): void
    {
        $attributes = ['venue_address', 'venue_city', 'venue_state', 'venue_zip', 'venue_country'];

        if ($event->isDirty($attributes))
        {
            $this->storeGeocode($event);
        }
    }

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

    private function buildGeocodeAddress($event): string
    {
        $parts = [];

        // Normalize the base address for duplicate detection
        $base = strtolower((string)$event->venue_address);

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
