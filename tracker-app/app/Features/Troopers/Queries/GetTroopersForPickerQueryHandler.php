<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\TrooperPickerMode;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving troopers for picker/dropdown components.
 *
 * Processes GetTroopersForPickerQuery to return troopers based on:
 * - Organization filtering: Returns only troopers belonging to a specific organization
 * - Guardian/minor rule: Minors can only be returned for their assigned guardian
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
     * 2. Apply guardian/minor visibility: include non-minors and minors only when the
     *    requesting trooper is their guardian
     * 3. If organization_id is set: Filter to troopers belonging to that organization
     * 4. If moderated_only is set: Filter to troopers moderated by the requesting trooper
     * 5. If filter has criteria: Apply search term and role filtering and return results
     * 6. If no filter criteria: Return empty collection
     *
     * @param  GetTroopersForPickerQuery  $message  The query containing filter and scope criteria
     * @return Collection<int, Trooper> Collection of filtered troopers, or empty if no filter applied
     */
    public function __invoke(object $message): mixed
    {
        $query = Trooper::active()
            ->whereNotNull(Trooper::SETUP_COMPLETED_AT)
            ->where(function ($q) use ($message) {
                $q->whereNull(Trooper::GUARDIAN_ID)
                    ->orWhere(Trooper::GUARDIAN_ID, $message->trooper->id);
            })
            ->orderBy(Trooper::DISPLAY_NAME);

        if ($message->organization_id)
        {
            $query = $query->whereHas('organizations', function ($q) use ($message) {
                $q->where('tt_organizations.id', $message->organization_id);
            });
        }

        if ($message->moderated_only)
        {
            $query = $query->moderatedBy($message->trooper);
        }

        $execute_query = false;
        $has_filter = $message->filter->hasFilter();

        if ($message->picker_mode == TrooperPickerMode::FRIENDS)
        {
            if (!$has_filter)
            {
                $q = TrooperFriend::query()
                    ->select(TrooperFriend::FRIEND_ID)
                    ->where(TrooperFriend::TROOPER_ID, $message->trooper->id);

                $query = $query->whereIn(Trooper::ID, $q);
            }

            $execute_query = true;
        }

        if ($has_filter)
        {
            $query = $query->filterWith($message->filter);

            $execute_query = true;
        }

        if ($execute_query)
        {
            return $query->get();
        }

        return collect([]);
    }
}
