<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving event trooper status change log.
 *
 * Returns EventTrooper records marked as ATTENDED within the lookback period,
 * where the status was changed by a moderator (not self-updated).
 *
 * @implements QueryHandlerInterface<GetStatusChangeLogQuery>
 */
readonly class GetStatusChangeLogQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve status change history.
     *
     * Retrieves EventTrooper records that were:
     * - Marked as ATTENDED
     * - Updated within the lookback period
     * - Updated by someone other than the trooper themselves
     * - For troopers moderated by the specified moderator
     *
     * @param  GetStatusChangeLogQuery  $message  The query containing moderator and lookback criteria.
     * @return Collection<int, EventTrooper> Collection of status changes.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $with = [
            'trooper',
            'event_shift.updated_by',
            'event_shift.event',
        ];

        $filter = function ($qx) use ($message) {
            $qx->moderatedBy($message->moderator);
        };

        return EventTrooper::with($with)
            ->whereHas('trooper', $filter)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->where(EventTrooper::UPDATED_AT, '>=', $lookback)
            ->whereColumn(EventTrooper::UPDATED_ID, '!=', EventTrooper::TROOPER_ID)
            ->orderByDesc(EventTrooper::UPDATED_AT)
            ->get();
    }
}
