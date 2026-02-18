<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use Illuminate\Support\Facades\Log;

/**
 * RebelLegionService
 *
 * Convert the original procedural Rebel Legion scraper into a project-style
 * synchronizer. It scrapes member information to find costume names/images
 * and updates organization costumes and membership pivots accordingly.
 */
class RebelLegionService extends BaseOrganizationService
{
    public function synchronize(): void
    {
        $costume_rows = $this->getSheetRows();

        foreach ($costume_rows as $row)
        {
            $forum_id = $this->cleanInput($row[0] ?? null);
            $costume_name = $this->cleanInput($row[1] ?? null);
            $costume_image = $this->cleanInput($row[2] ?? null);

            if (empty($costume_name))
            {
                continue;
            }

            // Map to organization costume and trooper costume
            $identifier = $forum_id . '';

            if (empty($identifier))
            {
                continue;
            }

            // Ensure organization costume exists
            $org_costume = $this->getOrCreateOrganizationCostume($costume_name);

            $trooper = $this->getTrooper($identifier);

            if ($trooper === null)
            {
                continue;
            }

            $this->syncTrooperStatus($trooper, MembershipStatus::ACTIVE);

            $this->syncTrooperCostume($trooper, $org_costume, $costume_image);
        }

        $this->updateOrganizationSync();
    }
}