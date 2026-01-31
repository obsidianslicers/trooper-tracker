<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use Carbon\Carbon;

/**
 * Handler for retrieving event summary statistics.
 *
 * Returns a collection of closed events moderated by the specified trooper,
 * with shift counts and trooper participation metrics (total and unique).
 *
 * @implements QueryHandlerInterface<GetEventSummaryQuery>
 */
readonly class GetEventSummaryQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve event summaries.
     *
     * Retrieves closed events within the lookback period and calculates:
     * - event_shifts_count: Number of shifts per event
     * - total_trooper_count: Total trooper signups across all shifts
     * - unique_trooper_count: Unique troopers across all shifts
     *
     * @param  GetEventSummaryQuery  $message  The query containing moderator and lookback criteria.
     * @return \Illuminate\Support\Collection<int, Event> Collection of events with summary data.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $with = [
            'event_shifts:id,event_id',
            'event_shifts.event_troopers' => function ($q)
            {
                $q->where('status', EventTrooperStatus::ATTENDED)->select('id', 'event_shift_id', 'trooper_id', 'status');
            },
        ];

        return Event::with($with)
            ->moderatedBy($message->moderator)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->where(Event::EVENT_START, '>=', $lookback)
            ->orderByDesc(Event::EVENT_END)
            ->get()
            ->each(function (Event $event)
            {
                $event->event_shifts_count = $event->event_shifts->count();
                // Total trooper rows across all shifts
                $event->total_trooper_count = $event->event_shifts->sum(fn($shift) => $shift->event_troopers->count());
                // Unique troopers across all shifts
                $event->unique_trooper_count = $event->event_shifts->flatMap(fn($shift) => $shift->event_troopers->pluck('trooper_id'))->unique()->count();
            });
    }
}
