<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Geocodes event addresses with the Google Maps API.
 */
class GoogleService
{
    /**
     * Get Latitude and Longitude from address using Google Maps API
     *
     * @param  string  $address  The address to geocode.
     * @return array An array containing latitude and longitude.
     */
    public function getLatitudeLongitude($address): array
    {
        $google_key = config('services.google.maps_api_key');

        // Get geo data from Google Maps API by address
        $geocode = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&key='.$google_key.'');

        // Decode JSON data returned by API
        $response = json_decode($geocode, false);

        // Check if API returned results, return null coordinates if not
        if (!isset($response->results) || empty($response->results))
        {
            return [null, null];
        }

        // Retrieve latitude and longitude from API data
        $latitude = $response->results[0]->geometry->location->lat;
        $longitude = $response->results[0]->geometry->location->lng;

        return [$latitude, $longitude];
    }
}
