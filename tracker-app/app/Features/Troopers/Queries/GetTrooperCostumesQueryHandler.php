<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;

/**
 * Handler for retrieving troopers for picker/dropdown components.
 *
 * Processes GetTroopersForPickerQuery to return troopers based on:
 * - Organization filtering: Returns only troopers belonging to a specific organization
 * - Filter criteria: Applies search term and role filtering via TrooperFilter
 *
 * All results are ordered by trooper name for consistent UI display.
 *
 * @implements QueryHandlerInterface<GetTrooperCostumesQuery>
 */
readonly class GetTrooperCostumesQueryHandler implements QueryHandlerInterface
{
    /**
     * Handle the query to retrieve troopers for picker components.
     *
     * Query behavior:
     * 1. Start with active troopers ordered by name
     * 2. If organization_id is set: Filter to troopers belonging to that organization
     * 3. If filter has criteria: Apply search term and role filtering
     * 4. Always return the result collection
     *
     * @param GetTrooperCostumesQuery $message The query containing filter criteria
     * @return \Illuminate\Support\Collection<int, Trooper> Collection of troopers
     */
    public function __invoke(object $message): mixed
    {
        /** @var GetTrooperCostumesQuery $message */

        return $message->trooper->trooper_costumes()->with('organization_costume.organization')->get();
    }
}