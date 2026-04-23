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

        $query = Event::query()
            ->select('tt_events.*')
            ->selectSub($attendeesCountSub, 'attendees_count')
            ->with('organization:id,name')
            ->moderatedBy($message->moderator)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->when($message->date_start, fn ($q) => $q->where(Event::EVENT_START, '>=', $message->date_start))
            ->when($message->date_end, fn ($q) => $q->where(Event::EVENT_START, '<=', $message->date_end))
            ->when($message->charity_only, fn ($q) => $q->where(function ($q) {
                $q->where(Event::CHARITY_DIRECT_FUNDS, '>', 0)
                    ->orWhere(Event::CHARITY_INDIRECT_FUNDS, '>', 0)
                    ->orWhereNotNull(Event::CHARITY_NAME);
            }));

        $allowed = ['name', 'event_start', 'charity_direct_funds', 'charity_indirect_funds', 'attendees_count'];
        $sort = in_array($message->sort, $allowed) ? $message->sort : 'event_start';
        $dir  = $message->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)->paginate($message->page_size)->withQueryString();
    }
}
