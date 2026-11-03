<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @method static LengthAwarePaginator call(Trooper $moderator, ?Carbon $date_start = null, ?Carbon $date_end = null, int $page_size = 50, ?Organization $organization = null, string $sort = 'uses_count', string $dir = 'desc', array $accessible_org_ids = [])
 */
final class GetCostumeEventSummary extends Message
{
    public function __construct(
        private readonly Trooper $moderator,
        private readonly ?Carbon $date_start = null,
        private readonly ?Carbon $date_end = null,
        private readonly int $page_size = 50,
        private readonly ?Organization $organization = null,
        private readonly string $sort = 'uses_count',
        private readonly string $dir = 'desc',
        private readonly array $accessible_org_ids = [],
    ) {}

    public function handle(): LengthAwarePaginator
    {
        $usesCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(*)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.costume_id', 'tt_costumes.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($this->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $this->date_start))
            ->when($this->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $this->date_end))
            ->when(
                $this->organization,
                fn ($q) => $q->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($this->organization->id)]),
                fn ($q) => !empty($this->accessible_org_ids)
                    ? $q->whereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($this->accessible_org_ids)])
                    : $q
            );

        $eventCountSub = DB::table('tt_event_troopers')
            ->selectRaw('COUNT(DISTINCT tt_event_shifts.event_id)')
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->whereColumn('tt_event_troopers.costume_id', 'tt_costumes.id')
            ->where('tt_event_troopers.status', EventTrooperStatus::ATTENDED->value)
            ->where('tt_events.status', EventStatus::CLOSED->value)
            ->when($this->date_start, fn ($q) => $q->where('tt_events.event_start', '>=', $this->date_start))
            ->when($this->date_end, fn ($q) => $q->where('tt_events.event_start', '<=', $this->date_end))
            ->when(
                $this->organization,
                fn ($q) => $q->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($this->organization->id)]),
                fn ($q) => !empty($this->accessible_org_ids)
                    ? $q->whereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($this->accessible_org_ids)])
                    : $q
            );

        $query = Costume::query()
            ->select('tt_costumes.*')
            ->selectSub($usesCountSub, 'uses_count')
            ->selectSub($eventCountSub, 'events_count')
            ->whereHas('event_troopers', function ($q)
            {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                    ->whereHas('event_shift.event', function ($q)
                    {
                        $q->where(Event::STATUS, EventStatus::CLOSED);

                        if ($this->date_start)
                        {
                            $q->where(Event::EVENT_START, '>=', $this->date_start);
                        }

                        if ($this->date_end)
                        {
                            $q->where(Event::EVENT_START, '<=', $this->date_end);
                        }
                    });

                if ($this->organization)
                {
                    $q->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($this->organization->id)]);
                }
                elseif (!empty($this->accessible_org_ids))
                {
                    $q->whereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($this->accessible_org_ids)]);
                }
            });

        $allowed = ['name', 'events_count', 'uses_count'];
        $sort = in_array($this->sort, $allowed) ? $this->sort : 'uses_count';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir)->paginate($this->page_size)->withQueryString();
    }
}
