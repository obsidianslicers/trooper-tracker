<?php

declare(strict_types=1);

namespace App\Features\Changes\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\EventTrooper;
use App\Models\StatusChange;
use App\Models\Trooper;
use Carbon\Carbon;

/**
 * Handler for retrieving model change history for a trooper.
 *
 * Returns a collection of StatusChange records representing changes to:
 * - The Trooper model itself (direct changes)
 * - EventTrooper records associated with the trooper
 *
 * Filters changes based on the lookback period specified in the query.
 *
 * @implements QueryHandlerInterface<GetStatusChangeLogQuery>
 */
readonly class GetStatusChangeLogQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve model change history.
     *
     * Converts the lookback parameter to a Carbon date if needed, then queries
     * StatusChange records for the trooper and their associated EventTrooper records.
     * Returns all changes since the lookback date.
     *
     * @param GetStatusChangeLogQuery $message The query containing trooper and lookback criteria.
     * @return \Illuminate\Support\Collection<int, EventTrooper> Collection of model changes.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->lookback;

        if (is_int($lookback))
        {
            $lookback = now()->subDays($lookback);
        }
        elseif (is_string($lookback))
        {
            $lookback = Carbon::parse($lookback);
        }

        $trooperIds = Trooper::moderatedBy($message->moderator)->pluck('id');

        $with = [
            'trooper',
            'event_shift.updated_by',
            'event_shift.event'
        ];

        return EventTrooper::with($with)
            ->whereIn(EventTrooper::TROOPER_ID, $trooperIds)
            ->where(EventTrooper::STATUS, 'attended')
            ->where(EventTrooper::UPDATED_AT, '>=', $lookback)
            ->whereColumn(EventTrooper::UPDATED_ID, '!=', EventTrooper::TROOPER_ID)
            ->orderByDesc(EventTrooper::UPDATED_AT)
            ->get();
    }
}