<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Models\OrganizationCostume;
use App\Models\Trooper;

/**
 * Retrieves available organization costumes that a trooper can add to their collection.
 *
 * This query service filters organization costumes to show only those that the trooper
 * does not already own within a specific organization. Used primarily when a trooper
 * wants to add a new costume to their approved costume collection.
 *
 * Workflow:
 * 1. Loads the trooper with their existing costume assignments
 * 2. Filters to find costumes already assigned within the organization
 * 3. Queries all organization costumes, excluding the already-assigned ones
 * 4. Returns formatted options for use in select dropdowns (name => id)
 *
 * This is a **query service** (not a command) - it retrieves data without side effects.
 *
 * Example use case:
 * - Trooper profile page showing "Add Costume" dropdown
 * - Admin interface for assigning new costumes to troopers
 * - Costume management forms requiring available costume selection
 */
class GetTrooperCostumesForOrganizationQuery
{
    /**
     * Execute the query to retrieve available organization costumes for a trooper.
     *
     * Returns costumes ordered alphabetically by name, formatted as key-value pairs
     * suitable for HTML select options (costume ID => costume name).
     *
     * @param int $trooperId The ID of the trooper requesting available costumes.
     * @param int $organization_id The ID of the organization to filter costumes by.
     * @return array<int, string> Associative array of costume options (id => name).
     */
    public function __invoke(int $trooperId, int $organization_id): array
    {
        $trooper = Trooper::with('trooper_costumes.organization_costume.organization')
            ->findOrFail($trooperId);

        $assignedIds = $trooper->trooper_costumes
            ->filter(fn($tc) => $tc->organization_costume?->organization_id === $organization_id)
            ->pluck('organization_costume.id')
            ->filter()
            ->values();

        return OrganizationCostume::with('organization')
            ->where(OrganizationCostume::ORGANIZATION_ID, $organization_id)
            ->excluding($assignedIds)
            ->orderBy(OrganizationCostume::NAME)
            ->toOptions(OrganizationCostume::NAME, OrganizationCostume::ID);
    }
}
