<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

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
    public function syncCostumes(): void
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
                Log::warning(__CLASS__ . " skipping trooper costume '{$costume_name}' with empty identifier for org {$this->organization->id}");

                continue;
            }

            // Ensure organization costume exists (do not set verified_at)
            $org_costume = $this->getOrganizationCostume($costume_name);

            $trooper = $this->getTrooper($identifier);

            if ($trooper === null)
            {
                Log::warning(__CLASS__ . " no trooper found for identifier '{$identifier}' for org {$this->organization->id}; skipping costume '{$costume_name}'");

                continue;
            }

            $this->syncTrooperCostume($trooper, $org_costume, $costume_image);
        }
    }

    public function syncAllMembers(): void
    {
        // Not supported for Rebel Legion
    }

    public function syncMember(string $identifier): void
    {
        // Not supported for Rebel Legion
    }
}