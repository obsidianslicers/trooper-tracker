<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Messages\Concerns\HasLookback;
use App\Messages\Concerns\HasTrooperOrgCreditQuery;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @method static array call(Costume $costume, int|string|Carbon|null $lookback = null, ?Organization $organization = null, int $limit = 30)
 */
final class GetCostumeTrooperLeaderboard extends Message
{
    use HasLookback {
        parseLookback as private parseRequiredLookback;
    }
    use HasTrooperOrgCreditQuery;

    public function __construct(
        private readonly Costume $costume,
        private readonly int|string|Carbon|null $lookback = null,
        private readonly ?Organization $organization = null,
        private readonly int $limit = 30,
    ) {}

    public function handle(): array
    {
        $lookback = $this->parseLookback();

        return [
            'top_troopers' => $this->getTopTroopers($lookback),
            'stats' => $this->getCostumeStats($lookback),
        ];
    }

    public function parseLookback(): ?Carbon
    {
        if ($this->lookback === null)
        {
            return null;
        }

        return $this->parseRequiredLookback();
    }

    private function getTopTroopers(?Carbon $date): Collection
    {
        $query = EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->where(EventTrooper::COSTUME_ID, $this->costume->id)
            ->whereHas('event_shift.event', function ($q) use ($date)
            {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->counted()
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->whereHas('trooper', function ($q)
            {
                $q->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE);
            })
            ->select(EventTrooper::TROOPER_ID, DB::raw('COUNT(*) as troop_count'))
            ->with(['trooper' => fn ($q) => $q->select(Trooper::ID, Trooper::DISPLAY_NAME)])
            ->groupBy(EventTrooper::TROOPER_ID);

        $this->applyTrooperOrgCredit($query, $this->resolveOrgSubtreeIds($this->organization), []);

        return $query
            ->orderByDesc('troop_count')
            ->take($this->limit)
            ->get();
    }

    private function getCostumeStats(?Carbon $date): array
    {
        $stats = EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->where(EventTrooper::COSTUME_ID, $this->costume->id)
            ->whereHas('event_shift.event', function ($q) use ($date)
            {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->select(
                DB::raw('COUNT(*) as total_deployments'),
                DB::raw('COUNT(DISTINCT trooper_id) as unique_troopers'),
            )
            ->first();

        $last_deployed = EventTrooper::where('tt_event_troopers.'.EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->where('tt_event_troopers.'.EventTrooper::COSTUME_ID, $this->costume->id)
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
