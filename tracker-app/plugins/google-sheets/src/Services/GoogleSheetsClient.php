<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\GoogleSheets\Services;

use App\Exceptions\SheetUnavailableException;
use Google\Service\Exception as GoogleServiceException;
use Google_Client;
use Google_Service_Sheets;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class GoogleSheetsClient
{
    /**
     * Extra attempts the API client makes on transient Google errors
     * (HTTP 429/500/503) using exponential backoff with jitter.
     */
    private const SHEET_RETRIES = 3;

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 10;

    private const HTTP_TIMEOUT_SECONDS = 30;

    public function __construct(private readonly string $credentials_path) {}

    public function isConfigured(): bool
    {
        return is_readable($this->credentials_path);
    }

    /**
     * Reads a range from a Google Sheet. Transient errors are retried first.
     *
     * @param  string  $get_range  Sheet name, or "Sheet Name!A1:G3"
     * @return array<int, array<int, string>> Row values, or [] when the range is empty
     *
     * @throws SheetUnavailableException when Google cannot be reached or the
     *                                   credentials file is missing
     */
    public function getSheet(string $spread_sheet_id, string $get_range): array
    {
        $service = new Google_Service_Sheets($this->makeClient());

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

            throw new SheetUnavailableException(
                "Google Sheets is unavailable for range [{$get_range}].",
                (int) $exception->getCode(),
                $exception
            );
        }

        return $response->getValues() ?? [];
    }

    private function makeClient(): Google_Client
    {
        if (!$this->isConfigured())
        {
            throw new SheetUnavailableException(
                "Google credentials file is missing at [{$this->credentials_path}]."
            );
        }

        $client = new Google_Client;
        $client->setApplicationName('Troop Tracker Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
        $client->setAccessType('offline');
        $client->setAuthConfig($this->credentials_path);
        $client->setConfig('retry', ['retries' => self::SHEET_RETRIES]);
        $client->setHttpClient(new GuzzleClient([
            'connect_timeout' => self::HTTP_CONNECT_TIMEOUT_SECONDS,
            'timeout' => self::HTTP_TIMEOUT_SECONDS,
        ]));

        return $client;
    }
}
