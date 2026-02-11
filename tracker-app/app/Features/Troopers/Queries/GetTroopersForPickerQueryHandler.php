<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Trooper;

/**
 * Handler for retrieving troopers for picker/dropdown components.
 *
 * Processes GetTroopersForPickerQuery to return troopers based on:
 * - Organization filtering: Returns only troopers belonging to a specific organization
 * - Filter criteria: Applies search term and role filtering via TrooperFilter
 *
 * All results are ordered by trooper name for consistent UI display.
 *
 * @implements QueryHandlerInterface<GetTroopersForPickerQuery>
 */
readonly class GetTroopersForPickerQueryHandler implements QueryHandlerInterface
{
    /**
     * Handle the query to retrieve troopers for picker components.
     *
     * Query behavior:
     * 1. Start with active troopers who have completed setup, ordered by name
     * 2. If organization_id is set: Filter to troopers belonging to that organization
     * 3. If moderated_only is set: Filter to troopers moderated by the requesting trooper
     * 4. If filter has criteria: Apply search term and role filtering and return results
     * 5. If no filter criteria: Return empty collection
     *
     * @param GetTroopersForPickerQuery $message The query containing filter and scope criteria
     * @return \Illuminate\Support\Collection<int, Trooper> Collection of filtered troopers, or empty if no filter applied
     */
    public function __invoke(object $message): mixed
    {
        $query = Trooper::active()
            ->whereNotNull(Trooper::SETUP_COMPLETED_AT)
            ->orderBy(Trooper::DISPLAY_NAME);

        if ($message->organization_id)
        {
            $query = $query->whereHas('organizations', function ($q) use ($message)
            {
                $q->where('tt_organizations.id', $message->organization_id);
            });
        }

        if ($message->moderated_only)
        {
            $query = $query->moderatedBy($message->trooper);
        }

        if ($message->filter->hasFilter())
        {
            $query = $query->filterWith($message->filter);

            return $query->get();
        }

        return collect([]);
    }
}