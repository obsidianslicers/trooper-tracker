<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Models\Event;
use App\Services\GeocodingService;
use App\Services\GoogleService;
use Throwable;

/**
 * Handles lifecycle events for the Event model.
 */
class EventObserver
{
    /**
     * Handle the Event "created" event.
     *
     * @param Event $event The event instance that was created.
     */
    public function created(Event $event): void
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
        $base = strtolower($event->venue_address);

        // Always start with the raw address field
        $parts[] = trim($event->venue_address);

        // Append city if not already included
        if (!str_contains($base, strtolower($event->venue_city)))
        {
            $parts[] = $event->venue_city;
        }

        // Append state if not already included
        if (!str_contains($base, strtolower($event->venue_state)))
        {
            $parts[] = $event->venue_state;
        }

        // Append ZIP if not already included
        if (!str_contains($base, strtolower($event->venue_zip)))
        {
            $parts[] = $event->venue_zip;
        }

        // Append country if not already included
        if (!str_contains($base, strtolower($event->venue_country)))
        {
            $parts[] = $event->venue_country;
        }

        // Join with commas for Nominatim
        return implode(', ', array_filter($parts));
    }
}
