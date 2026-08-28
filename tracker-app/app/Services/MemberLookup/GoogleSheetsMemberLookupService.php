<?php

declare(strict_types=1);

namespace App\Services\MemberLookup;

use App\Contracts\MemberLookupInterface;
use App\Exceptions\GoogleSheetsUnavailableException;
use App\Models\Organization;
use App\Services\GoogleService;
use Illuminate\Support\Facades\Cache;

class GoogleSheetsMemberLookupService implements MemberLookupInterface
{
    public function __construct(
        private readonly Organization $organization,
        private readonly GoogleService $google,
        private readonly string $sheet_name,
        private readonly string $identifier_header,
        private readonly string $name_header = '',
        private readonly string $identifier_prefix = '',
    ) {}

    public function lookup(string $identifier): ?array
    {
        $cache_key = "tracker:member-lookup:sheet:{$this->organization->id}:{$identifier}";

        if (Cache::has($cache_key))
        {
            $cached = Cache::get($cache_key);

            return $cached === false ? null : $cached;
        }

        try
        {
            $result = $this->fetchFromSheet($identifier);
        }
        catch (GoogleSheetsUnavailableException $exception)
        {
            // Google is temporarily down; report it but don't cache the miss so
            // the next lookup retries instead of failing silently for an hour.
            report($exception);

            return null;
        }

        Cache::put($cache_key, $result ?? false, 3600);

        return $result;
    }

    private function fetchFromSheet(string $identifier): ?array
    {
        $sheet_id = $this->organization->sync_sheet_id;

        if (empty($sheet_id))
        {
            return null;
        }

        $rows = $this->google->getSheet($sheet_id, $this->sheet_name);

        if (!is_array($rows) || empty($rows))
        {
            return null;
        }

        $headers = array_map('trim', $rows[0]);

        $identifier_col = $this->findColumn($headers, $this->identifier_header);

        if ($identifier_col === -1)
        {
            return null;
        }

        $name_col = !empty($this->name_header) ? $this->findColumn($headers, $this->name_header) : -1;

        foreach (array_slice($rows, 1) as $row)
        {
            $sheet_identifier = (string) ($row[$identifier_col] ?? '');

            if (!empty($this->identifier_prefix) && stripos($sheet_identifier, $this->identifier_prefix) === 0)
            {
                $sheet_identifier = substr($sheet_identifier, strlen($this->identifier_prefix));
            }

            if (strcasecmp($sheet_identifier, $identifier) !== 0)
            {
                continue;
            }

            $full_name = ($name_col >= 0) ? (($row[$name_col] ?? null) ?: null) : null;

            return [
                'identifier' => $identifier,
                'formatted_identifier' => $identifier,
                'full_name' => $full_name,
                'status' => 'Active',
                'standing' => 'Good',
                'is_approved' => true,
                'unit_name' => null,
                'profile_url' => null,
                'thumbnail_url' => null,
            ];
        }

        return null;
    }

    private function findColumn(array $headers, string $target): int
    {
        foreach ($headers as $index => $header)
        {
            if (strcasecmp(trim($header), trim($target)) === 0)
            {
                return $index;
            }
        }

        return -1;
    }
}
