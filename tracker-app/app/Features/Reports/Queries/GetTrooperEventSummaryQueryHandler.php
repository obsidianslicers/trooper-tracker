<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @implements QueryHandlerInterface<GetTrooperEventSummaryQuery>
 */
readonly class GetTrooperEventSummaryQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetTrooperEventSummaryQuery  $message
     * @return LengthAwarePaginator
     */
    public function __invoke(object $message): mixed
    {
        $shiftCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(*)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.trooper_id', 'tt_troopers.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end))
            ->when($message->organization, fn ($q) => $q->whereRaw(
                'JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)',
                [json_encode($message->organization->id)]
            ));

        $eventCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(DISTINCT tt_event_shifts.event_id)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.trooper_id', 'tt_troopers.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end))
            ->when($message->organization, fn ($q) => $q->whereRaw(
                'JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)',
                [json_encode($message->organization->id)]
            ));

        $query = Trooper::query()
            ->select('tt_troopers.*')
            ->selectSub($shiftCountSub, 'event_shifts_count')
            ->selectSub($eventCountSub, 'events_count')
            ->moderatedBy($message->moderator)
            ->whereHas('event_troopers', function ($q) use ($message) {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                    ->whereHas('event_shift.event', function ($q) use ($message) {
                        $q->where(Event::STATUS, EventStatus::CLOSED);
                        if ($message->date_start) {
                            $q->where(Event::EVENT_START, '>=', $message->date_start);
                        }
                        if ($message->date_end) {
                            $q->where(Event::EVENT_START, '<=', $message->date_end);
                        }
                    });

                if ($message->organization) {
                    $q->whereRaw(
                        'JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)',
                        [json_encode($message->organization->id)]
                    );
                }
            });

        if ($message->active_only) {
            $query->active();
        }

        $allowed = ['display_name', 'events_count', 'event_shifts_count'];
        $sort = in_array($message->sort, $allowed) ? $message->sort : 'event_shifts_count';
        $dir = $message->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)->paginate($message->page_size)->withQueryString();
    }
}
