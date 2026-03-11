<?php

declare(strict_types=1);

namespace App\Features\Changes\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Trooper;
use Carbon\Carbon;

/**
 * Handler for retrieving model change history for a trooper.
 *
 * Returns a collection of ModelChange records representing changes to:
 * - The Trooper model itself (direct changes)
 * - EventTrooper records associated with the trooper
 *
 * Filters changes based on the lookback period specified in the query.
 *
 * @implements QueryHandlerInterface<GetModelChangesForTrooperQuery>
 */
readonly class GetModelChangesForTrooperQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve model change history.
     *
     * Converts the lookback parameter to a Carbon date if needed, then queries
     * ModelChange records for the trooper and their associated EventTrooper records.
     * Returns all changes since the lookback date.
     *
     * @param  GetModelChangesForTrooperQuery  $message  The query containing trooper and lookback criteria.
     * @return \Illuminate\Support\Collection<int, ModelChange> Collection of model changes.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $trooper_filter = function ($q) use ($message) {
            // Direct changes to the Trooper model
            $q->where(ModelChange::AUDITABLE_TYPE, Trooper::class)
                ->where(ModelChange::AUDITABLE_ID, $message->trooper->id);
        };

        $event_trooper_filter = function ($q) use ($message) {
            // Changes to EventTrooper rows that belong to this Trooper
            $q->where(ModelChange::AUDITABLE_TYPE, EventTrooper::class)
                ->whereIn(ModelChange::AUDITABLE_ID, EventTrooper::query()
                    ->where(EventTrooper::TROOPER_ID, $message->trooper->id)
                    ->select('id')
                );
        };

        return ModelChange::where($trooper_filter)
            ->orWhere($event_trooper_filter)
            ->recent($lookback)
            ->get();
    }
}
