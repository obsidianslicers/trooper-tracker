<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Features\Events\Queries\HasOrgAttributionQuery;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @implements QueryHandlerInterface<GetCostumeTrooperLeaderboardQuery>
 */
readonly class GetCostumeTrooperLeaderboardQueryHandler implements QueryHandlerInterface
{
    use HasOrgAttributionQuery;

    public function __invoke(object $message): array
    {
        $lookback = $message->parseLookback();

        return [
            'top_troopers' => $this->getTopTroopers($message, $lookback),
            'stats' => $this->getCostumeStats($message, $lookback),
        ];
    }

    private function getTopTroopers(GetCostumeTrooperLeaderboardQuery $message, ?Carbon $date): Collection
    {
        $query = EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->where(EventTrooper::COSTUME_ID, $message->costume->id)
            ->whereHas('event_shift.event', function ($q) use ($date) {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->whereHas('trooper', function ($q) {
                $q->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE);
            })
            ->select(EventTrooper::TROOPER_ID, DB::raw('COUNT(*) as troop_count'))
            ->with(['trooper' => fn ($q) => $q->select(Trooper::ID, Trooper::DISPLAY_NAME)])
            ->groupBy(EventTrooper::TROOPER_ID);

        $this->applyOrgAttribution($query, $message->organization, []);

        return $query
            ->orderByDesc('troop_count')
            ->take($message->limit)
            ->get();
    }

    private function getCostumeStats(GetCostumeTrooperLeaderboardQuery $message, ?Carbon $date): array
    {
        $stats = EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->where(EventTrooper::COSTUME_ID, $message->costume->id)
            ->whereHas('event_shift.event', function ($q) use ($date) {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->select(
                DB::raw('COUNT(*) as total_deployments'),
                DB::raw('COUNT(DISTINCT trooper_id) as unique_troopers'),
            )
            ->first();

        $last_deployed = EventTrooper::where('tt_event_troopers.'.EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->where('tt_event_troopers.'.EventTrooper::COSTUME_ID, $message->costume->id)
            ->join('tt_event_shifts', 'tt_event_troopers.event_shift_id', '=', 'tt_event_shifts.id')
            ->join('tt_events', 'tt_event_shifts.event_id', '=', 'tt_events.id')
            ->where('tt_events.'.Event::STATUS, EventStatus::CLOSED)
            ->when($date, fn ($q) => $q->where('tt_events.'.Event::EVENT_START, '>=', $date))
            ->whereNull('tt_event_shifts.deleted_at')
            ->whereNull('tt_events.deleted_at')
            ->max('tt_events.event_start');

        return [
            'total_deployments' => (int) ($stats->total_deployments ?? 0),
            'unique_troopers' => (int) ($stats->unique_troopers ?? 0),
            'last_deployed_at' => $last_deployed ? Carbon::parse($last_deployed) : null,
        ];
    }
}
