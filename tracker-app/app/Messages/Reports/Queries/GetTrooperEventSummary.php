<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Messages\Concerns\HasTrooperOrgCreditQuery;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @method static LengthAwarePaginator call(Trooper $moderator, ?Carbon $date_start = null, ?Carbon $date_end = null, bool $active_only = false, int $page_size = 50, ?Organization $organization = null, string $sort = 'event_shifts_count', string $dir = 'desc', array $accessible_org_ids = [])
 */
final class GetTrooperEventSummary extends Message
{
    use HasTrooperOrgCreditQuery;

    public function __construct(
        private readonly Trooper $moderator,
        private readonly ?Carbon $date_start = null,
        private readonly ?Carbon $date_end = null,
        private readonly bool $active_only = false,
        private readonly int $page_size = 50,
        private readonly ?Organization $organization = null,
        private readonly string $sort = 'event_shifts_count',
        private readonly string $dir = 'desc',
        private readonly array $accessible_org_ids = [],
    ) {}

    public function handle(): LengthAwarePaginator
    {
        $roster_org_ids = $this->resolveOrgSubtreeIds($this->organization);

        $totals = $this->buildTotalsSubquery($roster_org_ids);

        $query = Trooper::query()
            ->select('tt_troopers.*', 'totals.event_shifts_count', 'totals.events_count')
            ->joinSub($totals, 'totals', 'totals.trooper_id', '=', 'tt_troopers.id')
            ->where(fn ($q) => $this->whereVisibleToModerator($q, $this->moderator));

        if ($this->active_only)
        {
            $query->active();
        }

        $allowed = ['display_name', 'events_count', 'event_shifts_count'];
        $sort = in_array($this->sort, $allowed) ? $this->sort : 'event_shifts_count';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)
            ->orderBy('tt_troopers.id')
            ->paginate($this->page_size)
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
    private function buildTotalsSubquery(array $roster_org_ids
    ): Builder {
        $sub = DB::table('tt_event_troopers')
            ->select('tt_event_troopers.trooper_id')
            ->selectRaw('COUNT(*) AS event_shifts_count')
            ->selectRaw('COUNT(DISTINCT tt_event_shifts.event_id) AS events_count')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->whereNotIn('tt_events.event_type_id', EventType::query()->uncountedIds())
            ->when($this->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $this->date_start))
            ->when($this->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $this->date_end))
            ->groupBy('tt_event_troopers.trooper_id');

        $this->applyTrooperOrgCredit($sub, $roster_org_ids, $this->accessible_org_ids);

        return $sub;
    }
}
