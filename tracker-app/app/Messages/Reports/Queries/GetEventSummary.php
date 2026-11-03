<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Messages\Concerns\HasLookback;
use App\Models\Event;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Query to retrieve event summary statistics for a moderator.
 *
 * Returns event counts and trooper participation metrics for closed events
 * within the specified lookback period.
 *
 * @method static Collection<int, Event>|LengthAwarePaginator<Event> call(Trooper $moderator, int|string|Carbon|null $lookback, bool $show_all = false, EventStatus $status = EventStatus::CLOSED, ?int $page_size = null)
 */
final class GetEventSummary extends Message
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $moderator  The moderator whose events to summarize.
     * @param  int|string|Carbon|null  $lookback  Days to look back (int), date string, Carbon date, or null for all history.
     */
    public function __construct(
        private readonly Trooper $moderator,
        private readonly int|string|Carbon|null $lookback,
        private readonly bool $show_all = false,
        private readonly EventStatus $status = EventStatus::CLOSED,
        private readonly ?int $page_size = null,
    ) {}

    /**
     * Execute the query to retrieve event summaries.
     *
     * Retrieves closed events within the lookback period and calculates:
     * - event_shifts_count: Number of shifts per event
     * - total_trooper_count: Total trooper signups across all shifts
     * - unique_trooper_count: Unique troopers across all shifts
     *
     * @return Collection<int, Event>|LengthAwarePaginator<Event> Events with summary data.
     */
    public function handle(): Collection|LengthAwarePaginator
    {
        $lookback = $this->parseLookback();

        $with = [
            'organization:id,name,image_path_sm',
            'organizations' => function ($q)
            {
                $q->select('tt_organizations.id', 'tt_organizations.name')->withPivot('id', 'can_attend');
            },
            'event_shifts:id,event_id,charity_direct_funds,charity_indirect_funds',
            'event_shifts.event_troopers' => function ($q)
            {
                $q->where('status', EventTrooperStatus::ATTENDED)->select('id', 'event_shift_id', 'trooper_id', 'status');
            },
        ];

        $q = Event::with($with);

        if (!$this->show_all)
        {
            $q = $q->moderatedBy($this->moderator);
        }

        $q->where(Event::STATUS, $this->status)
            ->when($lookback, fn ($q) => $q->where(Event::EVENT_START, '>=', $lookback))
            ->orderByDesc(Event::EVENT_END);

        $events = $this->page_size === null
            ? $q->get()
            : $q->paginate($this->page_size)->withQueryString();

        $collection = $events instanceof LengthAwarePaginator
            ? $events->getCollection()
            : $events;

        $collection->each(function (Event $event)
        {
            $event->event_shifts_count = $event->event_shifts->count();
            $event->total_trooper_count = $event->event_shifts->sum(fn ($shift) => $shift->event_troopers->count());
            $event->unique_trooper_count = $event->event_shifts->flatMap(fn ($shift) => $shift->event_troopers->pluck('trooper_id'))->unique()->count();
            $event->charity_direct_funds = $event->event_shifts->sum('charity_direct_funds');
            $event->charity_indirect_funds = $event->event_shifts->sum('charity_indirect_funds');
        });

        return $events;
    }
}
