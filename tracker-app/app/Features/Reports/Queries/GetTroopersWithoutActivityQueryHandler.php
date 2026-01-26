<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Models\EventTrooper;
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
 * @implements QueryHandlerInterface<GetTroopersWithoutActivityQuery>
 */
readonly class GetTroopersWithoutActivityQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve model change history.
     *
     * Converts the lookback parameter to a Carbon date if needed, then queries
     * StatusChange records for the trooper and their associated EventTrooper records.
     * Returns all changes since the lookback date.
     *
     * @param GetTroopersWithoutActivityQuery $message The query containing trooper and lookback criteria.
     * @return \Illuminate\Support\Collection<int, Trooper> Collection of model changes.
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

        $filter = function ($qx) use ($lookback)
        {
            $qx->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                ->where(EventTrooper::SIGNED_UP_AT, '<', $lookback);
        };

        return Trooper::moderatedBy($message->moderator)
            ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->whereDoesntHave('event_troopers', $filter)
            ->orderBy(Trooper::NAME)
            ->get();
    }
}