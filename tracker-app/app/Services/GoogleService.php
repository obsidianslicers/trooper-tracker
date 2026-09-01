<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\GoogleSheetsUnavailableException;
use Google\Service\Exception as GoogleServiceException;
use Google_Client;
use Google_Service_Sheets;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleService
{
    /**
     * Extra attempts the API client makes on transient Google errors
     * (HTTP 429/500/503) using exponential backoff with jitter.
     */
    private const SHEET_RETRIES = 3;

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 10;

    private const HTTP_TIMEOUT_SECONDS = 30;

    /**
     * Reads a range from a Google Sheet.
     *
     * Transient Google backend errors are retried automatically. If the sheet is
     * still unreachable afterwards a GoogleSheetsUnavailableException is thrown so
     * callers can skip the run instead of mistaking the outage for an empty sheet.
     *
     * @param  string  $spread_sheet_id  ID of the spreadsheet
     * @param  string  $get_range  Sheet name, or "Sheet Name!A1:G3"
     * @return array<int, array<int, string>> Row values, or [] when the range is empty
     *
     * @throws GoogleSheetsUnavailableException when Google cannot be reached
     */
    public function getSheet(string $spread_sheet_id, string $get_range): array
    {
        $service = new Google_Service_Sheets($this->makeSheetsClient());

        try
        {
            $response = $service->spreadsheets_values->get($spread_sheet_id, $get_range);
        }
        catch (GoogleServiceException|GuzzleException $exception)
        {
            Log::warning('Google Sheets request failed', [
                'spreadsheet_id' => $spread_sheet_id,
                'range' => $get_range,
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);

            throw new GoogleSheetsUnavailableException(
                "Google Sheets is unavailable for range [{$get_range}].",
                (int) $exception->getCode(),
                $exception
            );
        }

        return $response->getValues() ?? [];
    }

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

    private function makeSheetsClient(): Google_Client
    {
        $credentials = base_path('google-credentials.json');

        if (!is_file($credentials))
        {
            throw new RuntimeException("Google credentials file is missing at [{$credentials}].");
        }

        $client = new Google_Client;
        $client->setApplicationName('Troop Tracker Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
        $client->setAccessType('offline');
        $client->setAuthConfig($credentials);
        $client->setConfig('retry', ['retries' => self::SHEET_RETRIES]);
        $client->setHttpClient(new GuzzleClient([
            'connect_timeout' => self::HTTP_CONNECT_TIMEOUT_SECONDS,
            'timeout' => self::HTTP_TIMEOUT_SECONDS,
        ]));

        return $client;
    }
}
