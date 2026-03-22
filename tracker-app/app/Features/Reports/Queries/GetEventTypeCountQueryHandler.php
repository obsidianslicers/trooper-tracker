<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving event statistics grouped by type.
 *
 * Returns aggregated counts and trooper participation metrics for each event type
 * (e.g., Official, Unofficial, Training) within the lookback period.
 *
 * @implements QueryHandlerInterface<GetEventTypeCountQuery>
 */
readonly class GetEventTypeCountQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve event type statistics.
     *
     * Groups closed events by type and calculates for each:
     * - count: Number of events of this type
     * - total_trooper_count: Total signups across all events
     * - unique_trooper_count: Unique troopers across all events
     *
     * @param  GetEventTypeCountQuery  $message  The query containing moderator and lookback criteria.
     * @return Collection<int, object> Collection of event type statistics.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $total_counter = function ($event) {
            return $event->event_shifts->sum(fn ($shift) => $shift->event_troopers->count());
        };

        $unique_counter = function ($events) {
            $trooper_counter = function ($event) {
                return $event->event_shifts->flatMap(fn ($shift) => $shift->event_troopers->pluck('trooper_id'));
            };

            return $events->flatMap($trooper_counter)
                ->unique()
                ->count();
        };

        return Event::with('event_shifts.event_troopers')
            ->moderatedBy($message->moderator)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->where(Event::EVENT_START, '>=', $lookback)
            ->orderBy(Event::TYPE)
            ->get()
            ->groupBy(Event::TYPE)
            ->map(function ($events, $type) use ($total_counter, $unique_counter) {
                return (object) [
                    'event_type' => EventType::from($type),
                    'count' => $events->count(),
                    'total_trooper_count' => $events->sum($total_counter),
                    'unique_trooper_count' => $unique_counter($events),
                ];
            })
            ->values();
    }
}
