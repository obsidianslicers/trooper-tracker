<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving aggregated event statistics for all troopers.
 *
 * Aggregates event data for each trooper including total troops attended,
 * volunteer hours contributed, and funds raised (direct and indirect).
 * Results are ordered by event count descending for ranking purposes.
 *
 * @implements QueryHandlerInterface<GetTrooperEventStatsQuery>
 */
readonly class GetTrooperEventStatsQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve trooper event statistics.
     *
     * Process:
     * 1. Load troopers with closed-event relationships
     * 2. Aggregate counts, hours, and funds in PHP
     * 3. Return a mapped collection of stats
     * 4. Order by event_count descending (for ranking)
     *
     * @param GetTrooperEventStatsQuery $message The query (no parameters)
     * @return Collection Collection of trooper event statistics with fields:
     *                    - trooper_id: The trooper's ID
     *                    - event_count: Total number of events attended
     *                    - total_direct: Sum of direct funds raised
     *                    - total_indirect: Sum of indirect funds raised
     *                    - total_hours: Sum of volunteer hours
     */
    public function __invoke(object $message): mixed
    {
        // Load all troopers with their closed-event relationships
        $with = [
            'event_troopers' => function ($q)
            {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED);
            },
            'event_troopers.event_shift.event' => function ($q)
            {
                $q->where(Event::STATUS, EventStatus::CLOSED)->orderByDesc(Event::EVENT_END);
            }
        ];

        $troopers = Trooper::with($with)->get();

        // Map each trooper into an aggregated stats row
        foreach ($troopers as $trooper)
        {
            $event_troopers = $trooper->event_troopers;
            $event_count = $event_troopers->count();

            $total_direct = $event_troopers->sum(
                fn($et) => $et->event_shift->event->charity_direct_funds
            );

            $total_indirect = $event_troopers->sum(
                fn($et) => $et->event_shift->event->charity_indirect_funds
            );

            $total_hours = $event_troopers->sum(function ($et)
            {
                $shift = $et->event_shift;
                $event = $shift->event;
                $shift_hours = $shift->shift_starts_at->diffInHours($shift->shift_ends_at);

                return $shift_hours + $event->charity_hours;
            });

            $trooper->event_count = $event_count;
            $trooper->total_direct = $total_direct;
            $trooper->total_indirect = $total_indirect;
            $trooper->total_hours = $total_hours;
        }

        echo "count={$troopers->count()}" . PHP_EOL;

        return $troopers->sortByDesc('event_count')->values();
    }
}
