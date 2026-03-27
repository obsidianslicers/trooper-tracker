<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving the organization hierarchy.
 *
 * Returns a nested hierarchical structure of organizations → regions → units.
 * Each level contains 'id', 'name', and nested children arrays.
 *
 * @implements QueryHandlerInterface<GetOrganizationHierarchyQuery>
 */
readonly class GetOrganizationHierarchyQueryHandler implements QueryHandlerInterface
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
     * @param  int|null  $organization_id  Optional organization ID to filter to a single organization.
     * @return Collection<int, array{id: int, name: string, identifier_display: string, requires_guardian: bool, regions: Collection<int, array{id: int, name: string, units: Collection<int, array{id: int, name: string}>}>}> Collection of organizations with nested hierarchy.
     */
    public function __invoke(object $message): mixed
    {
        $q = Organization::fullyLoaded();

        if ($message->organization_id !== null)
        {
            $q->where(Organization::ID, $message->organization_id);
        }

        $organizations = $q->get();

        return $organizations->map(fn ($org) => [
            'id' => $org->id,
            'name' => $org->name,
            'identifier_display' => $org->identifier_display,
            'requires_guardian' => $org->requires_guardian,
            'regions' => $org->organizations->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'units' => $region->organizations->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ]),
            ]),
        ]);
    }
}
