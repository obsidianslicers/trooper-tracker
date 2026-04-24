<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @implements QueryHandlerInterface<GetCostumeEventSummaryQuery>
 */
readonly class GetCostumeEventSummaryQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetCostumeEventSummaryQuery  $message
     * @return LengthAwarePaginator
     */
    public function __invoke(object $message): mixed
    {
        $usesCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(*)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.costume_id', 'tt_costumes.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end))
            ->when(
                $message->organization,
                fn ($q) => $q->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($message->organization->id)]),
                fn ($q) => !empty($message->accessible_org_ids)
                    ? $q->whereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($message->accessible_org_ids)])
                    : $q
            );

        $eventCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(DISTINCT tt_event_shifts.event_id)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.costume_id', 'tt_costumes.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end))
            ->when(
                $message->organization,
                fn ($q) => $q->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($message->organization->id)]),
                fn ($q) => !empty($message->accessible_org_ids)
                    ? $q->whereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($message->accessible_org_ids)])
                    : $q
            );

        $query = Costume::query()
            ->select('tt_costumes.*')
            ->selectSub($usesCountSub, 'uses_count')
            ->selectSub($eventCountSub, 'events_count')
            ->whereHas('event_troopers', function ($q) use ($message) {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                    ->whereHas('event_shift.event', function ($q) use ($message) {
                        $q->where(Event::STATUS, EventStatus::CLOSED);

                        if ($message->date_start)
                        {
                            $q->where(Event::EVENT_START, '>=', $message->date_start);
                        }

                        if ($message->date_end)
                        {
                            $q->where(Event::EVENT_START, '<=', $message->date_end);
                        }
                    });

                if ($message->organization)
                {
                    $q->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($message->organization->id)]);
                }
                elseif (!empty($message->accessible_org_ids))
                {
                    $q->whereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($message->accessible_org_ids)]);
                }
            });

        $allowed = ['name', 'events_count', 'uses_count'];
        $sort = in_array($message->sort, $allowed) ? $message->sort : 'uses_count';
        $dir = $message->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)->paginate($message->page_size)->withQueryString();
    }
}
