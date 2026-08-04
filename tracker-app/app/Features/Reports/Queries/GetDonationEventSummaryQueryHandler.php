<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\HasTrooperOrgCreditQuery;
use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @implements QueryHandlerInterface<GetDonationEventSummaryQuery>
 */
readonly class GetDonationEventSummaryQueryHandler implements QueryHandlerInterface
{
    use HasTrooperOrgCreditQuery;

    /**
     * @param  GetDonationEventSummaryQuery  $message
     * @return LengthAwarePaginator
     */
    public function __invoke(object $message): mixed
    {
        $attendeesCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(DISTINCT tt_event_troopers.trooper_id)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->whereColumn('tt_event_shifts.event_id', 'tt_events.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value);

        $this->applyAttribution($attendeesCountSub, $message);

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
                'event_shifts' => function ($q) use ($message) {
                    $q->withCount(['event_troopers as attendees_count' => function ($inner) use ($message) {
                        $inner->where('status', EventTrooperStatus::ATTENDED->value);
                        $this->applyAttribution($inner, $message);
                    }])
                        ->orderBy('shift_starts_at');
                },
            ])
            ->moderatedBy($message->moderator)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->when($message->date_start, fn ($q) => $q->where(Event::EVENT_START, '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where(Event::EVENT_START, '<=', $message->date_end))
            ->when($message->charity_only, fn ($q) => $q->whereExists(function ($q) {
                $q->from('tt_event_shifts')
                    ->whereColumn('event_id', 'tt_events.id')
                    ->whereNull('deleted_at')
                    ->where(function ($inner) {
                        $inner->where('charity_direct_funds', '>', 0)
                            ->orWhere('charity_indirect_funds', '>', 0)
                            ->orWhere('charity_hours', '>', 0)
                            ->orWhere(fn ($q) => $q->whereNotNull('charity_name')->where('charity_name', '!=', ''))
                            ->orWhere(fn ($q) => $q->whereNotNull('charity_notes')->where('charity_notes', '!=', ''));
                    });
            }));

        $allowed = ['name', 'event_start', 'charity_direct_funds', 'charity_indirect_funds', 'charity_hours', 'attendees_count'];
        $sort = in_array($message->sort, $allowed) ? $message->sort : 'event_start';
        $dir = $message->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)
            ->paginate($message->page_size, ['*'], 'page', $message->page)
            ->withQueryString();
    }

    private function applyAttribution(mixed $q, GetDonationEventSummaryQuery $message): void
    {
        $this->applyTrooperOrgCredit($q, $message->selected_org_ids, $message->accessible_org_ids);
    }

    private function diffInHoursSql(string $start_col, string $end_col): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST((julianday({$end_col}) - julianday({$start_col})) * 24 AS INTEGER)"
            : "TIMESTAMPDIFF(HOUR, {$start_col}, {$end_col})";
    }
}
