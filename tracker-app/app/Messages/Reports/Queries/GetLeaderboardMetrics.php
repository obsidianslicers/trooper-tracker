<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Messages\Concerns\HasLookback;
use App\Messages\Concerns\HasTrooperOrgCreditQuery;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use DB;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve dashboard metrics for a lookback period.
 *
 * Provides the time window used by the dashboard metrics handler.
 *
 * @method static array<string, mixed> call(int|string|Carbon|null $lookback = null, ?Organization $organization = null, int $limit = 30)
 */
final class GetLeaderboardMetrics extends Message
{
    use HasLookback {
        parseLookback as private parseRequiredLookback;
    }
    use HasTrooperOrgCreditQuery;

    /**
     * Create a new query instance.
     *
     * @param  int|string|Carbon|null  $lookback  Days to look back, date string,
     *                                            Carbon date, or null for all time.
     */
    public function __construct(
        private readonly int|string|Carbon|null $lookback = null,
        private readonly ?Organization $organization = null,
        private readonly int $limit = 30,
    ) {}

    /**
     * Build the full dashboard metrics payload for the given lookback period.
     *
     * @return array<string, mixed> Metrics grouped by section keys.
     */
    public function handle(): array
    {
        $lookback = $this->parseLookback();

        return [
            'dominance' => $this->getOrganizationDominance($lookback),
            'diversity' => $this->getCostumeDiversity($lookback),
            'operatives' => $this->getOperatives($lookback),
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

    private function getOrganizationDominance(?Carbon $date): Collection
    {
        // 1. Sector Dominance (Club vs Club)
        // Aggregates total deployment volume per major club type
        return Organization::where(Organization::TYPE, OrganizationType::ORGANIZATION)
            ->whereNull(Organization::PARENT_ID) // Top level Legions/Clubs
            ->withCount(['events' => function ($q) use ($date)
            {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            }])
            ->get()
            ->sortByDesc('events_count')
            ->values();
    }

    private function getCostumeDiversity(?Carbon $date): Collection
    {
        // 2. Battle Readiness (Unit vs Unit)
        return EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->whereHas('event_shift.event', function ($q) use ($date)
            {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->whereNotNull('costume_id')
            ->select('costume_id', DB::raw('count(*) as occurrence_count'))
            ->whereDoesntHave('costume', function ($q)
            {
                $q->whereIn(Costume::NAME, ['N/A', 'NA', Costume::COMMAND_STAFF, Costume::HANDLER]);
            })
            ->with(['costume' => function ($q)
            {
                $q->select(Costume::ID, Costume::NAME);
            }])
            ->groupBy('costume_id')
            ->orderByDesc('occurrence_count')
            ->take(5)
            ->get()
            ->map(function ($record)
            {
                return [
                    'id' => $record->costume->id ?? null,
                    'name' => $record->costume->name ?? 'Unknown Kit',
                    'count' => $record->occurrence_count,
                ];
            });
    }

    private function getOperatives(?Carbon $date): Collection
    {
        // 3. Shadow Operatives (Individual Superlatives)
        // We look for the "Iron Suits" - Troopers with the most attended records
        $query = EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
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
            ->select(EventTrooper::TROOPER_ID, DB::raw('count(*) as troop_count'))
            ->with(['trooper' => fn ($q) => $q->select(Trooper::ID, Trooper::DISPLAY_NAME)])
            ->groupBy(EventTrooper::TROOPER_ID);

        $this->applyTrooperOrgCredit($query, $this->resolveOrgSubtreeIds($this->organization), []);

        return $query
            ->orderByDesc('troop_count')
            ->take($this->limit)
            ->get();
    }
}
