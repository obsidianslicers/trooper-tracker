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
 * Handler for retrieving troopers without recent event activity.
 *
 * Returns active troopers who have not attended any events since
 * before the lookback date (i.e., inactive during the lookback period).
 *
 * @implements QueryHandlerInterface<GetTroopersWithoutActivityQuery>
 */
readonly class GetTroopersWithoutActivityQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to find inactive troopers.
     *
     * Retrieves active troopers managed by the moderator who:
     * - Have no ATTENDED event signups since the lookback date
     * - Are currently in ACTIVE membership status
     *
     * @param  GetTroopersWithoutActivityQuery  $message  The query containing moderator and lookback criteria.
     * @return \Illuminate\Support\Collection<int, Trooper> Collection of inactive troopers.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $filter = function ($qx) use ($lookback)
        {
            $qx->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                ->where(EventTrooper::SIGNED_UP_AT, '<', $lookback);
        };

        return Trooper::moderatedBy($message->moderator)
            ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->whereDoesntHave('event_troopers', $filter)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }
}
