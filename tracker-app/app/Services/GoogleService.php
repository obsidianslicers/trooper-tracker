<?php

declare(strict_types=1);

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;

class GoogleService
{
    /**
     * Get's Google Sheet
     *
     * @param int $spread_sheet_id ID of the spreadsheet
     * @param int $get_range Sheet Name OR Sheet Name!A1:G3
     * @return string Returns values from spreadsheet
     */
    public function getSheet(string $spread_sheet_id, string $get_range): mixed
    {
        // Google API set up
        $client = new Google_Client();
        $client->setApplicationName('Troop Tracker Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType(accessType: 'offline');
        $client->setAuthConfig(base_path() . '/google-credentials.json');
        $service = new Google_Service_Sheets($client);
        $response = $service->spreadsheets_values->get($spread_sheet_id, $get_range);
        $values = $response->getValues();
        return $values;
    }

    /**
     * Get Latitude and Longitude from address using Google Maps API
     *
     * @param string $address The address to geocode.
     * @return array An array containing latitude and longitude.
     */
    public function getLatitudeLongitude($address): array
    {
        // Get geo data from Google Maps API by address 
        $geocode = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . googleKey . "");

        // Decode JSON data returned by API 
        $response = json_decode($geocode, false);

        // Retrieve latitude and longitude from API data 
        $latitude = $response->results[0]->geometry->location->lat;
        $longitude = $response->results[0]->geometry->location->lng;

        return [$latitude, $longitude];
    }
}