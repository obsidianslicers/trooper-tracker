<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Contracts\MemberLookupInterface;
use App\Services\MemberLookup\GoogleSheetsMemberLookupService;

/**
 * Service class for managing Mandalorian Mercs organization data.
 *
 * This service interacts with Google Sheets to synchronize member information,
 * update trooper statuses, and manage organization-specific data.
 */
class MandalorianMercsService extends BaseOrganizationService
{
    public function lookupMember(): ?MemberLookupInterface
    {
        return new GoogleSheetsMemberLookupService($this->organization, $this->google, 'Troopers', 'ID', 'Name');
    }

    protected function synchronize(): void {}
}
