<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @implements QueryHandlerInterface<GetTrooperEventSummaryQuery>
 */
readonly class GetTrooperEventSummaryQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $shiftCountSub = $this->buildShiftCountSubquery($message);
        $eventCountSub = $this->buildEventCountSubquery($message);

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

                $this->applyTrooperOrgCredit($q, $message->organization, $message->accessible_org_ids);
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

    private function buildShiftCountSubquery(GetTrooperEventSummaryQuery $message): Builder
    {
        $sub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(*)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.trooper_id', 'tt_troopers.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end));

        $this->applyTrooperOrgCredit($sub, $message->organization, $message->accessible_org_ids);

        return $sub;
    }

    private function buildEventCountSubquery(GetTrooperEventSummaryQuery $message): Builder
    {
        $sub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(DISTINCT tt_event_shifts.event_id)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.trooper_id', 'tt_troopers.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end));

        $this->applyTrooperOrgCredit($sub, $message->organization, $message->accessible_org_ids);

        return $sub;
    }

    /**
     * An event shift only counts toward a trooper's totals when the trooper is a roster
     * member of the selected organization (or one of its descendants), and the credit on
     * the event_trooper record was given to that trooper's own organization or to any
     * ancestor of it (e.g. credit logged at the garrison level still counts for a squad
     * member). This mirrors how squad leaders actually attribute troop credit: the specific
     * squad an event was tagged with doesn't matter, only whether the credit reaches the
     * trooper's own place in the org hierarchy.
     */
    private function applyTrooperOrgCredit(mixed $q, ?Organization $organization, array $accessible_org_ids): void
    {
        $node_path = $organization?->node_path;

        if (!$node_path && empty($accessible_org_ids))
        {
            return;
        }

        $q->whereExists(function ($sub) use ($node_path, $accessible_org_ids) {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_credit')
                ->join('tt_organizations as trooper_org', 'ta_credit.organization_id', '=', 'trooper_org.id')
                ->whereColumn('ta_credit.trooper_id', 'tt_event_troopers.trooper_id')
                ->where('ta_credit.is_member', true);

            if ($node_path)
            {
                $sub->whereRaw('trooper_org.node_path LIKE ?', [$node_path.'%']);
            }
            else
            {
                $sub->whereIn(
                    DB::raw('CAST(SUBSTRING_INDEX(trooper_org.node_path, \':\', 1) AS UNSIGNED)'),
                    $accessible_org_ids
                );
            }

            // trooper_org.node_path is a colon-delimited chain of ancestor ids (e.g. "501:42:").
            // Turning it into a JSON array lets us test, in one expression, whether any id the
            // event was credited to (via costume_organization_ids) is the trooper's own org or
            // one of its ancestors.
            $ancestor_ids_json = "CONCAT('[', REPLACE(TRIM(TRAILING ':' FROM trooper_org.node_path), ':', ','), ']')";

            $sub->where(function ($sub) use ($ancestor_ids_json) {
                $sub->where(function ($sub) use ($ancestor_ids_json) {
                    $this->whereHasCostumeOrganizationCredit($sub);
                    $sub->whereRaw("JSON_OVERLAPS($ancestor_ids_json, tt_event_troopers.costume_organization_ids)");
                })
                    ->orWhere(function ($sub) {
                        $this->whereNoCostumeOrganizationCredit($sub);
                        $sub->whereRaw(
                            '(trooper_org.node_path LIKE CONCAT(tt_event_troopers.organization_id, \':%\') '.
                            'OR trooper_org.node_path LIKE CONCAT(\'%:\', tt_event_troopers.organization_id, \':%\'))'
                        );
                    });
            });
        });
    }

    private function whereHasCostumeOrganizationCredit(mixed $q): void
    {
        $json_path = "REPLACE(tt_event_troopers.costume_organization_ids, ' ', '')";

        $q->whereNotNull('tt_event_troopers.costume_organization_ids')
            ->whereRaw($json_path.' != ?', ['[]']);
    }

    private function whereNoCostumeOrganizationCredit(mixed $q): void
    {
        $q->where(function ($q) {
            $q->whereNull('tt_event_troopers.costume_organization_ids')
                ->orWhereRaw(
                    "REPLACE(CAST(tt_event_troopers.costume_organization_ids AS CHAR), ' ', '') = ?",
                    ['[]']
                );
        });
    }
}
