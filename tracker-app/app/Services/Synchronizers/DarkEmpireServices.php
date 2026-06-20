<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Contracts\MemberLookupInterface;

/**
 * Service class for managing Mandalorian Mercs organization data.
 *
 * This service interacts with Google Sheets to synchronize member information,
 * update trooper statuses, and manage organization-specific data.
 */
class DarkEmpireServices extends BaseOrganizationService
{
    public function lookupMember(): ?MemberLookupInterface
    {
        return null;
    }

    protected function synchronize(): void {}
}
