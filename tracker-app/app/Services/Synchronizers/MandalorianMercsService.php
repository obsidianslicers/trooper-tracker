<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Contracts\MemberLookupInterface;
use App\Services\MemberLookup\SheetMemberLookupService;

/**
 * Service class for managing Mandalorian Mercs organization data.
 *
 * This service reads the club roster spreadsheet to synchronize member information,
 * update trooper statuses, and manage organization-specific data.
 */
class MandalorianMercsService extends BaseOrganizationService
{
    public function lookupMember(): ?MemberLookupInterface
    {
        return new SheetMemberLookupService($this->organization, $this->sheets, 'Troopers', 'ID', 'Name');
    }

    protected function synchronize(): void {}
}
