<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\HasTrooperOrgCreditQuery;
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
    use HasTrooperOrgCreditQuery;

    public function __invoke(object $message): mixed
    {
        $roster_org_ids = $this->resolveOrgSubtreeIds($message->organization);

        $totals = $this->buildTotalsSubquery($message, $roster_org_ids);

        $query = Trooper::query()
            ->select('tt_troopers.*', 'totals.event_shifts_count', 'totals.events_count')
            ->joinSub($totals, 'totals', 'totals.trooper_id', '=', 'tt_troopers.id')
            ->where(fn ($q) => $this->whereVisibleToModerator($q, $message->moderator));

        if ($message->active_only)
        {
            $query->active();
        }

        $allowed = ['display_name', 'events_count', 'event_shifts_count'];
        $sort = in_array($message->sort, $allowed) ? $message->sort : 'event_shifts_count';
        $dir = $message->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)
            ->orderBy('tt_troopers.id')
            ->paginate($message->page_size)
            ->withQueryString();
    }

    /**
     * Troopers the moderator currently oversees, plus former members who still hold attended
     * credit inside the moderator's organizations (their member assignments are removed when
     * they leave a club, so moderatedBy alone would hide their history).
     */
    private function whereVisibleToModerator(mixed $q, Trooper $moderator): void
    {
        if ($moderator->is_administrator)
        {
            return;
        }

        $moderated_org_ids = Organization::moderatedBy($moderator)->pluck(Organization::ID)->all();

        $q->moderatedBy($moderator)
            ->orWhereHas('event_troopers', function ($q) use ($moderated_org_ids)
            {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED);
                $this->whereDirectCredit($q, $moderated_org_ids);
            });
    }

    /**
     * Aggregates attended shifts and unique events per trooper in a single pass, so the
     * org-credit filter (JSON and LIKE predicates that cannot use an index) is evaluated once
     * per attendance row instead of once per correlated subquery per trooper.
     */
    private function buildTotalsSubquery(
        GetTrooperEventSummaryQuery $message,
        array $roster_org_ids
    ): Builder {
        $sub = DB::table('tt_event_troopers')
            ->select('tt_event_troopers.trooper_id')
            ->selectRaw('COUNT(*) AS event_shifts_count')
            ->selectRaw('COUNT(DISTINCT tt_event_shifts.event_id) AS events_count')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($message->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $message->date_end))
            ->groupBy('tt_event_troopers.trooper_id');

        $this->applyTrooperOrgCredit($sub, $roster_org_ids, $message->accessible_org_ids);

        return $sub;
    }
}
