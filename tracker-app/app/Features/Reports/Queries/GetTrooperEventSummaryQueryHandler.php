<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;

/**
 * Handler for retrieving trooper event participation summary.
 *
 * Returns all troopers who attended events within the lookback period,
 * with calculated statistics for shift and event attendance.
 *
 * @implements QueryHandlerInterface<GetTrooperEventSummaryQuery>
 */
readonly class GetTrooperEventSummaryQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve trooper event summaries.
     *
     * Retrieves troopers with attended events and calculates:
     * - event_shifts_count: Total shifts attended
     * - events_count: Unique events attended
     * - attended_event_ids: List of unique event IDs
     *
     * @param  GetTrooperEventSummaryQuery  $message  The query containing moderator and lookback criteria.
     * @return \Illuminate\Support\Collection<int, Trooper> Collection of troopers with summary data.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $with = [
            'event_troopers' => function ($q) use ($lookback)
            {
                $columns = [
                    EventTrooper::ID,
                    EventTrooper::EVENT_SHIFT_ID,
                    EventTrooper::TROOPER_ID,
                    EventTrooper::STATUS,
                ];

                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                    ->whereHas('event_shift.event', function ($q) use ($lookback)
                    {
                        $q->where(Event::STATUS, EventStatus::CLOSED)
                            ->where(Event::EVENT_START, '>=', $lookback);
                    })->select($columns);
            },
            'event_troopers.event_shift:id,event_id',
            'event_troopers.event_shift.event:id,event_start,event_end,status',
        ];

        return Trooper::with($with)
            ->whereHas('event_troopers.event_shift.event', function ($q) use ($lookback)
            {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->where(Event::EVENT_START, '>=', $lookback);
            })
            ->orderBy(Trooper::NAME)
            ->get()->each(function (Trooper $trooper)
            {
                // Total attended shifts
                $trooper->event_shifts_count = $trooper->event_troopers->count();
                // Unique events attended
                $trooper->events_count = $trooper->event_troopers->map(fn($et) => $et->event_shift->event_id)->unique()->count();
                // Optional: list of event IDs
                $trooper->attended_event_ids = $trooper->event_troopers->map(fn($et) => $et->event_shift->event_id)->unique()->values();
            });
    }
}
