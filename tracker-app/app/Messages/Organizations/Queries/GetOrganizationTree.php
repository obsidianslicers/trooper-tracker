<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Queries;

use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve the complete organization hierarchy.
 *
 * Returns all organizations in a hierarchical structure (organizations, regions, and units)
 * with nested relationships. Optionally filters to a specific organization and its descendants.
 *
 * @method static Collection<int, array{id: int, name: string, identifier_display: string, requires_guardian: bool, regions: Collection<int, array{id: int, name: string, units: Collection<int, array{id: int, name: string}>}>}> call(?int $organization_id = null)
 */
final class GetOrganizationTree extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  int|null  $organization_id  Optional organization ID to filter hierarchy to specific org and descendants
     */
    public function __construct(private readonly ?int $organization_id = null) {}

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
     * @return Collection<int, array{id: int, name: string, identifier_display: string, requires_guardian: bool, regions: Collection<int, array{id: int, name: string, units: Collection<int, array{id: int, name: string}>}>}> Collection of organizations with nested hierarchy.
     */
    public function handle(): Collection
    {
        $q = Organization::fullyLoaded();

        if ($this->organization_id !== null)
        {
            $q->where(Organization::ID, $this->organization_id);
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
