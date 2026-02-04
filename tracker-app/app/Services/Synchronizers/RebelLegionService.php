<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\OrganizationCostume;
use App\Models\TrooperOrganization;
use App\Models\TrooperUpload;
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

                // Create or update a TrooperUpload record for this costume
                // Map sheet columns to tt_trooper_uploads fields:
                // - identifier => 'Rebel Legion Forum Username' (as provided in sheet)
                // - costume_name => 'Costume Name'
                // - large_image_url => 'Costume Image URL'
                $identifier = is_null($legionId) ? '' : (string) $legionId;

                if (empty($identifier)) {
                    // If no identifier present, log and skip
                    Log::warning("RebelLegionService: skipping costume '{$costumeName}' with empty identifier for org {$this->organization->id}");
                    continue;
                }

                $upload = TrooperUpload::where('organization_id', $this->organization->id)
                    ->where('identifier', $identifier)
                    ->where('costume_name', $costumeName)
                    ->first();

                $data = [
                    'organization_id' => $this->organization->id,
                    'identifier' => $identifier,
                    'costume_name' => $costumeName,
                    'large_image_url' => $costumeImage ?: null,
                ];

                if ($upload === null) {
                    TrooperUpload::create($data);
                } else {
                    $shouldUpdate = false;
                    if (($upload->large_image_url ?? null) !== ($data['large_image_url'] ?? null)) {
                        $upload->large_image_url = $data['large_image_url'];
                        $shouldUpdate = true;
                    }

                    if ($shouldUpdate) {
                        $upload->save();
                    }
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