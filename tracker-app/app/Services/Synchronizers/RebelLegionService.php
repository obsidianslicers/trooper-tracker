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
        $sheet_id = $this->organization->sync_sheet_id ?? null;

        if (empty($sheet_id))
        {
            Log::info("RebelLegionService: no sync_sheet_id configured for organization {$this->organization->name}; skipping syncCostumes.");
            return;
        }

        $google_service = app(\App\Services\GoogleService::class);

        // Costumes sheet columns expected: [legionId, costumename, costumeimage]
        $costume_rows = $google_service->getSheet($sheet_id, 'Costumes');

        if (is_array($costume_rows))
        {
            $first = true;
            foreach ($costume_rows as $row)
            {
                if ($first) { $first = false; continue; } // skip header

                $legion_id      = $row[0] ?? null;
                $costume_name   = $row[1] ?? null;
                $costume_image  = $row[2] ?? null;

                if (empty($costume_name)) { continue; }

                // Map to organization costume and trooper costume
                $identifier = is_null($legion_id) ? '' : (string) $legion_id;

                if (empty($identifier))
                {
                    Log::warning("RebelLegionService: skipping costume '{$costume_name}' with empty identifier for org {$this->organization->id}");
                    continue;
                }

                // Ensure organization costume exists (do not set verified_at)
                $org_costume = $this->organization->organization_costumes()
                    ->where('name', $costume_name)
                    ->first();

                if ($org_costume === null)
                {
                    $org_costume = new OrganizationCostume();
                    $org_costume->organization_id = $this->organization->id;
                    $org_costume->name = $costume_name;
                    $org_costume->verified_at = null;
                    $org_costume->save();
                }

                // find trooper by identifier on pivot
                $trooper = $this->organization->troopers()
                    ->wherePivot(TrooperOrganization::IDENTIFIER, $identifier)
                    ->first();

                if ($trooper === null)
                {
                    // no matching trooper in local DB
                    continue;
                }

                // create or update trooper_costume
                $trooper_costume = TrooperCostume::where('trooper_id', $trooper->id)
                    ->where('costume_id', $org_costume->id)
                    ->first();

                $tc_data = [
                    'trooper_id' => $trooper->id,
                    'costume_id' => $org_costume->id,
                    'costume_prefix' => null,
                    'small_image_url' => null,
                    'large_image_url' => $costume_image ?: null,
                    'bucket_off_url' => null,
                ];

                if ($trooper_costume === null)
                {
                    TrooperCostume::create($tc_data);
                } else {
                    $changed = false;
                    foreach (['large_image_url'] as $k) {
                        if (($trooper_costume->{$k} ?? null) !== ($tc_data[$k] ?? null)) {
                            $trooper_costume->{$k} = $tc_data[$k] ?? null;
                            $changed = true;
                        }
                    }
                    if ($changed) { $trooper_costume->save(); }
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