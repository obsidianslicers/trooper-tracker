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
use Illuminate\Database\Query\Builder;
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
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end));

        $this->applyOrgAttribution($shiftCountSub, $message, true);

        $eventCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(DISTINCT tt_event_shifts.event_id)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.trooper_id', 'tt_troopers.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end));

        $this->applyOrgAttribution($eventCountSub, $message, true);

        $query = Trooper::query()
            ->select('tt_troopers.*')
            ->selectSub($shiftCountSub, 'event_shifts_count')
            ->selectSub($eventCountSub, 'events_count')
            ->moderatedBy($message->moderator)
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

                $this->applyOrgAttribution($q, $message, false);
            });

        if ($message->active_only)
        {
            $query->active();
        }

        $allowed = ['display_name', 'events_count', 'event_shifts_count'];
        $sort = in_array($message->sort, $allowed) ? $message->sort : 'event_shifts_count';
        $dir = $message->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)->paginate($message->page_size)->withQueryString();
    }

    /**
     * Apply org attribution rules to a query builder.
     *
     * Rule 1: explicit organization_id set on event_trooper → credit only that org
     * Rule 2: no explicit org, costume_organization_ids contains the org → credit it
     * Unattached troops (no explicit org, no costume orgs) are not counted toward any org.
     *
     * @param  bool  $check_join_date  Whether to gate on tt_trooper_organizations.join_date (requires tt_events to be joined)
     */
    private function applyOrgAttribution(mixed $q, GetTrooperEventSummaryQuery $message, bool $check_join_date): void
    {
        if ($message->organization)
        {
            $org_id = $message->organization->id;

            $q->where(function ($q) use ($org_id) {
                // Rule 1: trooper explicitly chose this org
                $q->where('tt_event_troopers.organization_id', $org_id)
                  // Rule 2: no explicit org, costume approved for this org
                    ->orWhere(function ($q) use ($org_id) {
                        $q->whereNull('tt_event_troopers.organization_id')
                            ->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($org_id)]);
                    });
            });

            if ($check_join_date)
            {
                $node_path = $message->organization->node_path;
                $q->whereExists(function ($q) use ($node_path) {
                    $q->from('tt_trooper_organizations as tto')
                        ->join('tt_organizations as o', 'tto.organization_id', '=', 'o.id')
                        ->whereColumn('tto.trooper_id', 'tt_event_troopers.trooper_id')
                        ->where('o.node_path', 'LIKE', $node_path.'%')
                        ->whereNull('tto.deleted_at')
                        ->where(function ($q) {
                            $q->whereNull('tto.join_date')
                                ->orWhereRaw('tto.join_date <= tt_events.event_start');
                        });
                });
            }
        }
        elseif (!empty($message->accessible_org_ids))
        {
            $accessible_org_ids = $message->accessible_org_ids;
            $encoded = json_encode($accessible_org_ids);

            $q->where(function ($q) use ($accessible_org_ids, $encoded) {
                // Rule 1: explicit org resolves to an accessible root org
                $q->whereExists(function ($q) use ($accessible_org_ids) {
                    $q->from('tt_organizations as et_org')
                        ->whereColumn('et_org.id', 'tt_event_troopers.organization_id')
                        ->whereIn(
                            DB::raw("CAST(SUBSTRING_INDEX(et_org.node_path, ':', 1) AS UNSIGNED)"),
                            $accessible_org_ids
                        );
                })
                  // Rule 2: costume org overlaps accessible root orgs
                    ->orWhereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [$encoded]);
            });
        }
    }
}
