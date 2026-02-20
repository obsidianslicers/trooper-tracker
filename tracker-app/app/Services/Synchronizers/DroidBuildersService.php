<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\TrooperCostume;

/**
 * DroidBuildersService
 *
 * This service aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
 */
class DroidBuildersService extends BaseOrganizationService
{
    protected function synchronize(): void
    {
        $costume_rows = $this->getSheetRows('Sheet1');

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
            $identifier = $forum_id.'';

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

            $attributes = [
                TrooperCostume::IMAGE_URL_LG => $costume_image,
            ];

            $this->syncTrooperCostume($trooper, $org_costume, $attributes);
        }
    }
}
