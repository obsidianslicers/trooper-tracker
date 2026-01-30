<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\OrganizationCostume;
use App\Models\TrooperOrganization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * RebelLegionService
 *
 * Convert the original procedural Rebel Legion scraper into a project-style
 * synchronizer. It scrapes member profile pages to find costume names/images
 * and updates organization costumes and membership pivots accordingly.
 */
class RebelLegionService extends BaseOrganizationService
{
    public function syncCostumes(): void
    {
        // Use the organization's configured sheet id; if not set, skip syncing.
        $sheetId = $this->organization->sync_sheet_id ?? null;

        if (empty($sheetId)) {
            Log::info("RebelLegionService: no sync_sheet_id configured for organization {$this->organization->name}; skipping syncCostumes.");
            return;
        }

        $google = app(\App\Services\GoogleService::class);

        // Costumes sheet columns expected: [legionId, costumename, costumeimage]
        $costumeRows = $google->getSheet($sheetId, 'Costumes');

        if (is_array($costumeRows))
        {
            $first = true;
            foreach ($costumeRows as $row)
            {
                if ($first) { $first = false; continue; } // skip header

                $legionId      = $row[0] ?? null;
                $costumeName   = $row[1] ?? null;
                $costumeImage  = $row[2] ?? null;

                if (empty($costumeName)) { continue; }

                // Find existing costume for THIS organization by name
                $costume = $this->organization->organization_costumes()
                    ->where('name', $costumeName)
                    ->first();

                if ($costume === null)
                {
                    $costume = new OrganizationCostume();
                    $costume->organization_id = $this->organization->id;
                    $costume->name = $costumeName;
                }

                $updates = [];

                // Always mark verified
                if (Schema::hasColumn('tt_organization_costumes', 'verified_at'))
                {
                    $updates['verified_at'] = now();
                }
                else
                {
                    // if you *know* the model has it regardless of schema checks, you can set directly
                    $costume->verified_at = now();
                }

                // Only update image if provided (prevents blanking existing values)
                if (!empty($costumeImage) && Schema::hasColumn('tt_organization_costumes', 'image_path'))
                {
                    $updates['image_path'] = $costumeImage;
                }

                if (!empty($updates))
                {
                    foreach ($updates as $k => $v) { $costume->{$k} = $v; }
                }

                $costume->save();
            }
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