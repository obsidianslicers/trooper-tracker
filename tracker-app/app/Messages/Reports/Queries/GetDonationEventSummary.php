<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Messages\Concerns\HasTrooperOrgCreditQuery;
use App\Models\Event;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @method static LengthAwarePaginator call(Trooper $moderator, ?Carbon $date_start = null, ?Carbon $date_end = null, bool $charity_only = false, int $page_size = 50, string $sort = 'event_start', string $dir = 'desc', array $selected_org_ids = [], array $accessible_org_ids = [], ?int $page = null)
 */
final class GetDonationEventSummary extends Message
{
    use HasTrooperOrgCreditQuery;

    public function __construct(
        private readonly Trooper $moderator,
        private readonly ?Carbon $date_start = null,
        private readonly ?Carbon $date_end = null,
        private readonly bool $charity_only = false,
        private readonly int $page_size = 50,
        private readonly string $sort = 'event_start',
        private readonly string $dir = 'desc',
        private readonly array $selected_org_ids = [],
        private readonly array $accessible_org_ids = [],
        private readonly ?int $page = null,
    ) {}

    public function handle(): LengthAwarePaginator
    {
        $attendeesCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(DISTINCT tt_event_troopers.trooper_id)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->whereColumn('tt_event_shifts.event_id', 'tt_events.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value);

        $this->applyAttribution($attendeesCountSub);

        $directFundsSub = DB::table('tt_event_shifts')
            ->selectRaw('COALESCE(SUM(charity_direct_funds), 0)')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at');

        $indirectFundsSub = DB::table('tt_event_shifts')
            ->selectRaw('COALESCE(SUM(charity_indirect_funds), 0)')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at');

        $charityHoursSub = DB::table('tt_event_shifts')
            ->selectRaw('SUM(COALESCE(charity_hours, '.$this->diffInHoursSql('shift_starts_at', 'shift_ends_at').'))')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at');

        $query = Event::query()
            ->select('tt_events.*')
            ->selectSub($attendeesCountSub, 'attendees_count')
            ->selectSub($directFundsSub, 'charity_direct_funds')
            ->selectSub($indirectFundsSub, 'charity_indirect_funds')
            ->selectSub($charityHoursSub, 'charity_hours')
            ->with([
                'organization:id,name',
                'event_shifts' => function ($q)
                {
                    $q->withCount(['event_troopers as attendees_count' => function ($inner)
                    {
                        $inner->where('status', EventTrooperStatus::ATTENDED->value);
                        $this->applyAttribution($inner);
                    }])
                        ->orderBy('shift_starts_at');
                },
            ])
            ->moderatedBy($this->moderator)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->when($this->date_start, fn ($q) => $q->where(Event::EVENT_START, '>=', $this->date_start))
            ->when($this->date_end, fn ($q) => $q->where(Event::EVENT_START, '<=', $this->date_end))
            ->when($this->charity_only, fn ($q) => $q->whereExists(function ($q)
            {
                $q->from('tt_event_shifts')
                    ->whereColumn('event_id', 'tt_events.id')
                    ->whereNull('deleted_at')
                    ->where(function ($inner)
                    {
                        $inner->where('charity_direct_funds', '>', 0)
                            ->orWhere('charity_indirect_funds', '>', 0)
                            ->orWhere('charity_hours', '>', 0)
                            ->orWhere(fn ($q) => $q->whereNotNull('charity_name')->where('charity_name', '!=', ''))
                            ->orWhere(fn ($q) => $q->whereNotNull('charity_notes')->where('charity_notes', '!=', ''));
                    });
            }));

        $allowed = ['name', 'event_start', 'charity_direct_funds', 'charity_indirect_funds', 'charity_hours', 'attendees_count'];
        $sort = in_array($this->sort, $allowed) ? $this->sort : 'event_start';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)
            ->paginate($this->page_size, ['*'], 'page', $this->page)
            ->withQueryString();
    }

    private function applyAttribution(mixed $q): void
    {
        $this->applyTrooperOrgCredit($q, $this->selected_org_ids, $this->accessible_org_ids);
    }

    private function diffInHoursSql(string $start_col, string $end_col): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST((julianday({$end_col}) - julianday({$start_col})) * 24 AS INTEGER)"
            : "TIMESTAMPDIFF(HOUR, {$start_col}, {$end_col})";
    }
}
