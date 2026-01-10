<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Models\Event;
use Exception;


/**
 * Service class for managing Mandalorian Mercs organization data.
 *
 * This service interacts with Google Sheets to synchronize member information,
 * update trooper statuses, and manage organization-specific data.
 */
class DarkEmpireServices extends BaseOrganizationService
{
    public function syncCostumes(): void
    {
    }

    public function syncAllMembers(): void
    {
    }

    public function syncMember(string $identifier): void
    {
    }
}
