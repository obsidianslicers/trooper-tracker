<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Retrieves the hierarchical structure of organizations, regions, and units.
 *
 * This service query fetches the three-level organizational hierarchy used by
 * Star Wars costuming clubs (Organizations → Regions → Units). It returns a
 * structured collection suitable for dropdowns, navigation menus, and reports.
 *
 * The hierarchy structure:
 * - **Organizations** (top level) - e.g., 501st Legion, Rebel Legion
 * - **Regions** (children of organizations) - e.g., Southern California Garrison
 * - **Units** (children of regions) - e.g., Squad 7, Outpost Delta
 *
 * Uses Organization::fullyLoaded() scope to eager load all three levels,
 * preventing N+1 queries. Can optionally filter to a single organization by ID.
 *
 * This is a **query service** (not a command) - it retrieves data without side effects.
 */
class GetOrganizationHierarchyQuery
{
    /**
     * Execute the query to retrieve organization hierarchy.
     *
     * Returns a collection of organizations with nested regions and units,
     * structured as an array with 'id', 'name', 'regions', and 'units' keys.
     *
     * Example structure:
     * ```
     * [
     *   ['id' => 1, 'name' => '501st Legion', 'regions' => [
     *     ['id' => 2, 'name' => 'SoCal Garrison', 'units' => [
     *       ['id' => 3, 'name' => 'Squad 7']
     *     ]]
     *   ]]
     * ]
     * ```
     *
     * @param int|null $organization_id Optional organization ID to filter to a single organization.
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, regions: \Illuminate\Support\Collection<int, array{id: int, name: string, units: \Illuminate\Support\Collection<int, array{id: int, name: string}>}>}> Collection of organizations with nested hierarchy.
     */
    public function __invoke(?int $organization_id = null): Collection
    {
        $q = Organization::fullyLoaded();

        if ($organization_id !== null)
        {
            $q->where(Organization::ID, $organization_id);
        }

        $organizations = $q->get();

        return $organizations->map(fn($org) => [
            'id' => $org->id,
            'name' => $org->name,
            'identifier_display' => $org->identifier_display,
            'regions' => $org->organizations->map(fn($region) =>
                [
                    'id' => $region->id,
                    'name' => $region->name,
                    'units' => $region->organizations->map(fn($unit) =>
                        [
                            'id' => $unit->id,
                            'name' => $unit->name,
                        ]),
                ]),
        ]);

    }
}
