<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
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
        $troopers = Trooper::query()
            ->with([
                'event_troopers.event_shift.event' => fn($q) => $q->where('status', EventStatus::CLOSED)
            ])
            ->get();

        // Map each trooper into an aggregated stats row
        $stats = $troopers->map(function (Trooper $trooper)
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

            return (object) [
                'trooper_id' => $trooper->id,
                'event_count' => $event_count,
                'total_direct' => $total_direct,
                'total_indirect' => $total_indirect,
                'total_hours' => $total_hours,
            ];
        });

        // Order by event_count descending to match original SQL behavior
        return $stats->sortByDesc('event_count')->values();
    }
}
