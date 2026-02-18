<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use Illuminate\Support\Facades\Log;


/**
 * Service class for managing Mandalorian Mercs organization data.
 *
 * This service interacts with Google Sheets to synchronize member information,
 * update trooper statuses, and manage organization-specific data.
 */
class SaberGuildServices extends BaseOrganizationService
{
    public function synchronize(): void
    {
        $costume_rows = $this->getSheetRows();

        foreach ($costume_rows as $row)
        {
            $name = $this->cleanInput($row[0] ?? null);
            $rank_title = $this->cleanInput($row[1] ?? null);
            $forum_id = $this->cleanInput($row[2] ?? null);
            $costume_name = $this->cleanInput($row[3] ?? null);
            $costume_image = $this->cleanInput($row[4] ?? null);

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

            // Convert Google Drive link
            if (strpos($costume_image, "view?usp=drivesdk") !== false)
            {
                $segments = explode("/", $costume_image);
                $costume_image = "https://drive.google.com/uc?id=" . $segments[5] . "";
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
