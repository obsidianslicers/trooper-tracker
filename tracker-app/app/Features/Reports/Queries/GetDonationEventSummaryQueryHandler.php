<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @implements QueryHandlerInterface<GetDonationEventSummaryQuery>
 */
readonly class GetDonationEventSummaryQueryHandler implements QueryHandlerInterface
{
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

        $directFundsSub = DB::table('tt_event_shifts')
            ->selectRaw('COALESCE(SUM(charity_direct_funds), 0)')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at');

        $indirectFundsSub = DB::table('tt_event_shifts')
            ->selectRaw('COALESCE(SUM(charity_indirect_funds), 0)')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at');

        $charityHoursSub = DB::table('tt_event_shifts')
            ->selectRaw('COALESCE(SUM(charity_hours), 0)')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at');

        $charityNameSub = DB::table('tt_event_shifts')
            ->select('charity_name')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at')
            ->whereNotNull('charity_name')
            ->where('charity_name', '!=', '')
            ->orderBy('shift_starts_at')
            ->limit(1);

        $charityNotesSub = DB::table('tt_event_shifts')
            ->select('charity_notes')
            ->whereColumn('event_id', 'tt_events.id')
            ->whereNull('deleted_at')
            ->whereNotNull('charity_notes')
            ->where('charity_notes', '!=', '')
            ->orderBy('shift_starts_at')
            ->limit(1);

        $query = Event::query()
            ->select('tt_events.*')
            ->selectSub($attendeesCountSub, 'attendees_count')
            ->selectSub($directFundsSub, 'charity_direct_funds')
            ->selectSub($indirectFundsSub, 'charity_indirect_funds')
            ->selectSub($charityHoursSub, 'charity_hours')
            ->selectSub($charityNameSub, 'charity_name')
            ->selectSub($charityNotesSub, 'charity_notes')
            ->with('organization:id,name')
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

        $allowed = ['name', 'event_start', 'charity_name', 'charity_direct_funds', 'charity_indirect_funds', 'charity_hours', 'attendees_count'];
        $sort = in_array($message->sort, $allowed) ? $message->sort : 'event_start';
        $dir = $message->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)->paginate($message->page_size)->withQueryString();
    }
}
