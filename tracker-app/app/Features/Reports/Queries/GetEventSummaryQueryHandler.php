<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * @return Collection<int, Event>|LengthAwarePaginator<Event> Events with summary data.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $with = [
            'organization:id,name,image_path_sm',
            'organizations' => function ($q) {
                $q->select('tt_organizations.id', 'tt_organizations.name')->withPivot('id', 'can_attend');
            },
            'event_shifts:id,event_id,charity_direct_funds,charity_indirect_funds',
            'event_shifts.event_troopers' => function ($q) {
                $q->where('status', EventTrooperStatus::ATTENDED)->select('id', 'event_shift_id', 'trooper_id', 'status');
            },
        ];

        $q = Event::with($with);

        if (!$message->show_all)
        {
            $q = $q->moderatedBy($message->moderator);
        }

        $q->where(Event::STATUS, $message->status)
            ->when($lookback, fn ($q) => $q->where(Event::EVENT_START, '>=', $lookback))
            ->orderByDesc(Event::EVENT_END);

        $events = $message->page_size === null
            ? $q->get()
            : $q->paginate($message->page_size)->withQueryString();

        $collection = $events instanceof LengthAwarePaginator
            ? $events->getCollection()
            : $events;

        $collection->each(function (Event $event) {
            $event->event_shifts_count = $event->event_shifts->count();
            $event->total_trooper_count = $event->event_shifts->sum(fn ($shift) => $shift->event_troopers->count());
            $event->unique_trooper_count = $event->event_shifts->flatMap(fn ($shift) => $shift->event_troopers->pluck('trooper_id'))->unique()->count();
            $event->charity_direct_funds = $event->event_shifts->sum('charity_direct_funds');
            $event->charity_indirect_funds = $event->event_shifts->sum('charity_indirect_funds');
        });

        return $events;
    }
}
