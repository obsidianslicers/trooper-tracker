<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\OrganizationCostume;
use App\Models\TrooperOrganization;
use App\Models\TrooperCostume;
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
 * synchronizer. It scrapes member information to find costume names/images
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

                // Map to organization costume and trooper costume
                $identifier = is_null($legionId) ? '' : (string) $legionId;

                if (empty($identifier)) {
                    Log::warning("RebelLegionService: skipping costume '{$costumeName}' with empty identifier for org {$this->organization->id}");
                    continue;
                }

                // Ensure organization costume exists (do not set verified_at)
                $orgCostume = $this->organization->organization_costumes()
                    ->where('name', $costumeName)
                    ->first();

                if ($orgCostume === null) {
                    $orgCostume = new OrganizationCostume();
                    $orgCostume->organization_id = $this->organization->id;
                    $orgCostume->name = $costumeName;
                    $orgCostume->verified_at = null;
                    $orgCostume->save();
                }

                // find trooper by identifier on pivot
                $trooper = $this->organization->troopers()
                    ->wherePivot(TrooperOrganization::IDENTIFIER, $identifier)
                    ->first();

                if ($trooper === null) {
                    // no matching trooper in local DB
                    continue;
                }

                // create or update TrooperCostume
                $tc = TrooperCostume::where('trooper_id', $trooper->id)
                    ->where('costume_id', $orgCostume->id)
                    ->first();

                $tcData = [
                    'trooper_id' => $trooper->id,
                    'costume_id' => $orgCostume->id,
                    'costume_prefix' => null,
                    'small_image_url' => null,
                    'large_image_url' => $costumeImage ?: null,
                    'bucket_off_url' => null,
                ];

                if ($tc === null) {
                    TrooperCostume::create($tcData);
                } else {
                    $changed = false;
                    foreach (['large_image_url'] as $k) {
                        if (($tc->{$k} ?? null) !== ($tcData[$k] ?? null)) {
                            $tc->{$k} = $tcData[$k] ?? null;
                            $changed = true;
                        }
                    }
                    if ($changed) { $tc->save(); }
                }
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