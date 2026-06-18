<?php

declare(strict_types=1);

namespace App\Services\MemberLookup;

use App\Contracts\MemberLookupInterface;
use App\Models\Organization;
use App\Services\GoogleService;

class GoogleSheetsMemberLookupService implements MemberLookupInterface
{
    public function __construct(
        private readonly Organization $organization,
        private readonly GoogleService $google,
        private readonly string $sheet_name,
        private readonly int $identifier_column,
        private readonly int $name_column = -1,
        private readonly string $identifier_prefix = '',
    ) {}

    public function lookup(string $identifier): ?array
    {
        $sheet_id = $this->organization->sync_sheet_id;

        if (empty($sheet_id))
        {
            return null;
        }

        $rows = $this->google->getSheet($sheet_id, $this->sheet_name);

        if (!is_array($rows))
        {
            return null;
        }

        // Skip header row
        $rows = array_slice($rows, 1);

        foreach ($rows as $row)
        {
            $sheet_identifier = (string) ($row[$this->identifier_column] ?? '');

            if (!empty($this->identifier_prefix) && stripos($sheet_identifier, $this->identifier_prefix) === 0)
            {
                $sheet_identifier = substr($sheet_identifier, strlen($this->identifier_prefix));
            }

            if (strcasecmp($sheet_identifier, $identifier) !== 0)
            {
                continue;
            }

            $full_name = ($this->name_column >= 0) ? ($row[$this->name_column] ?? null) : null;

            return [
                'identifier'           => $identifier,
                'formatted_identifier' => $identifier,
                'full_name'            => $full_name,
                'status'               => 'Active',
                'standing'             => 'Good',
                'is_approved'          => true,
                'unit_name'            => null,
                'profile_url'          => null,
                'thumbnail_url'        => null,
            ];
        }

        return null;
    }
}
