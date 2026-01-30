<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
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
 * @implements QueryHandlerInterface<GetEventSummaryQuery>
 */
readonly class GetEventSummaryQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve model change history.
     *
     * Converts the lookback parameter to a Carbon date if needed, then queries
     * StatusChange records for the trooper and their associated EventTrooper records.
     * Returns all changes since the lookback date.
     *
     * @param  GetEventSummaryQuery  $message  The query containing trooper and lookback criteria.
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
