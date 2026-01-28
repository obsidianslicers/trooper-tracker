<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventVolunteer;
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
 * @implements QueryHandlerInterface<GetEventVolunteerCountQuery>
 */
readonly class GetEventVolunteerCountQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve model change history.
     *
     * Converts the lookback parameter to a Carbon date if needed, then queries
     * StatusChange records for the trooper and their associated EventTrooper records.
     * Returns all changes since the lookback date.
     *
     * @param GetEventVolunteerCountQuery $message The query containing trooper and lookback criteria.
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

        $total_counter = function ($event)
        {
            return $event->event_shifts->sum(fn($shift) => $shift->event_troopers->count());
        };

        $unique_counter = function ($events)
        {
            $trooper_counter = function ($event)
            {
                return $event->event_shifts->flatMap(fn($shift) => $shift->event_troopers->pluck('trooper_id'));
            };

            return $events->flatMap($trooper_counter)
                ->unique()
                ->count();
        };

        return Event::with('event_shifts.event_troopers')
            ->moderatedBy($message->moderator)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->where(Event::EVENT_START, '>=', $lookback)
            ->orderByDesc(Event::EVENT_END)
            ->get()->each(function (Event $event)
            {
                $event->event_shifts_count = $event->event_shifts->count();
                // Total trooper rows across all shifts
                $event->total_trooper_count = $event->event_shifts->sum(fn($shift) => $shift->event_troopers->count());
                // Unique troopers across all shifts
                $event->unique_trooper_count = $event->event_shifts->flatMap(fn($shift) => $shift->event_troopers->pluck('trooper_id'))->unique()->count();
            });

    }
}