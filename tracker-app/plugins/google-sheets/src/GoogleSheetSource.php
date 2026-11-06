<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\GoogleSheets;

use App\Contracts\SheetSource;
use App\Models\Organization;
use TroopTracker\Plugins\GoogleSheets\Models\OrganizationSheet;
use TroopTracker\Plugins\GoogleSheets\Services\GoogleSheetsClient;

/**
 * Reads a club's roster from the Google Sheet saved on its organization page.
 */
final class GoogleSheetSource implements SheetSource
{
    public function __construct(private readonly GoogleSheetsClient $client) {}

    public function rows(Organization $organization, string $sheet_name): ?array
    {
        $sheet_id = OrganizationSheet::forOrganization($organization->id);

        return $sheet_id === null ? null : $this->client->getSheet($sheet_id, $sheet_name);
    }
}
