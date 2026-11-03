<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Messages\Concerns\HasLookback;
use App\Models\Event;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve event statistics grouped by event type.
 *
 * Returns counts and trooper participation metrics for each event type
 * within the specified lookback period.
 *
 * @method static Collection<int, object> call(Trooper $moderator, int|string|Carbon $lookback)
 */
final class GetEventTypeCount extends Message
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $moderator  The moderator whose events to analyze.
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        private readonly Trooper $moderator,
        private readonly int|string|Carbon $lookback,
    ) {}

    /**
     * Execute the query to retrieve event type statistics.
     *
     * Groups closed events by type and calculates for each:
     * - count: Number of events of this type
     * - total_trooper_count: Total signups across all events
     * - unique_trooper_count: Unique troopers across all events
     *
     * @return Collection<int, object> Collection of event type statistics.
     */
    public function handle(): Collection
    {
        $lookback = $this->parseLookback();

        $total_counter = function ($event)
        {
            return $event->event_shifts->sum(fn ($shift) => $shift->event_troopers->count());
        };

        $unique_counter = function ($events)
        {
            $trooper_counter = function ($event)
            {
                return $event->event_shifts->flatMap(fn ($shift) => $shift->event_troopers->pluck('trooper_id'));
            };

            return $events->flatMap($trooper_counter)
                ->unique()
                ->count();
        };

        return Event::with('event_shifts.event_troopers')
            ->moderatedBy($this->moderator)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->where(Event::EVENT_START, '>=', $lookback)
            ->orderBy(Event::TYPE)
            ->get()
            ->groupBy(Event::TYPE)
            ->map(function ($events, $type) use ($total_counter, $unique_counter)
            {
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
