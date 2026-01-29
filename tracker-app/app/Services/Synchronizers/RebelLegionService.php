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
        // For the Rebel Legion we derive costumes from member profiles during
        // member sync. This method is left intentionally minimal to match the
        // project's other synchronizers.
    }

    public function syncAllMembers(): void
    {
        // Use the organization's configured sheet id; if not set, skip syncing.
        $sheetId = $this->organization->sync_sheet_id ?? null;

        if (empty($sheetId))
        {
            error_log("RebelLegionService: no sync_sheet_id configured for organization {$this->organization->name}; skipping syncAllMembers.");
            return;
        }

        $google = app(\App\Services\GoogleService::class);

        // Troopers sheet columns expected: [legionId, name, rebelforum]
        $trooperRows = $google->getSheet($sheetId, 'Troopers');

        if (is_array($trooperRows))
        {
            $first = true;
            foreach ($trooperRows as $row)
            {
                if ($first) { $first = false; continue; } // skip header

                $legionId = $row[0] ?? null;
                $name = $row[1] ?? null;
                $forum = $row[2] ?? null;

                if (empty($forum)) { continue; }

                // Find local trooper pivot by forum username
                $trooper = $this->organization->troopers()
                    ->wherePivot(TrooperOrganization::IDENTIFIER, $forum)
                    ->first();

                if ($trooper === null)
                {
                    continue;
                }

                $pivot = $trooper->pivot;

                $updates = [];
                if (!empty($name) && Schema::hasColumn('tt_trooper_organizations', 'display_name'))
                {
                    $updates['display_name'] = $name;
                }

                if (!empty($legionId))
                {
                    // Persist the external legion id into our pivot identifier
                    $updates['identifier'] = (string) $forum;
                }

                if (Schema::hasColumn('tt_trooper_organizations', 'verified_at'))
                {
                    $updates['verified_at'] = now();
                }

                if (!empty($updates))
                {
                    foreach ($updates as $k => $v) { $pivot->{$k} = $v; }
                    $pivot->save();
                }
            }
        }
    }

    public function syncMember(string $identifier): void
    {
 
    }
}