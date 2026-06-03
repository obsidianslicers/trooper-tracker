<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Features\Events\Queries\HasOrgAttributionQuery;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Handler for the admin metrics dashboard.
 *
 * Optimized for selectable intervals: 30, 60, 90, 180, 360 days.
 *
 * @implements QueryHandlerInterface<GetLeaderboardMetricsQuery>
 */
readonly class GetLeaderboardMetricsQueryHandler implements QueryHandlerInterface
{
    use HasOrgAttributionQuery;

    /**
     * Build the full dashboard metrics payload for the given lookback period.
     *
     * @param  GetLeaderboardMetricsQuery  $message  The query containing lookback criteria.
     * @return array<string, mixed> Metrics grouped by section keys.
     */
    public function __invoke(object $message): array
    {
        $lookback = $message->parseLookback();

        return [
            'dominance' => $this->getOrganizationDominance($lookback),
            'diversity' => $this->getCostumeDiversity($lookback),
            'operatives' => $this->getOperatives($lookback, $message),
        ];
    }

    private function getOrganizationDominance(?Carbon $date): Collection
    {
        // 1. Sector Dominance (Club vs Club)
        // Aggregates total deployment volume per major club type
        return Organization::where(Organization::TYPE, OrganizationType::ORGANIZATION)
            ->whereNull(Organization::PARENT_ID) // Top level Legions/Clubs
            ->withCount(['events' => function ($q) use ($date) {
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
            ->whereHas('event_shift.event', function ($q) use ($date) {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->whereNotNull('costume_id')
            ->select('costume_id', \DB::raw('count(*) as occurrence_count'))
            ->whereDoesntHave('costume', function ($q) {
                $q->whereIn(Costume::NAME, ['N/A', 'NA', Costume::COMMAND_STAFF, Costume::HANDLER]);
            })
            ->with(['costume' => function ($q) {
                $q->select(Costume::ID, Costume::NAME);
            }])
            ->groupBy('costume_id')
            ->orderByDesc('occurrence_count')
            ->take(5)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->costume->id ?? null,
                    'name' => $record->costume->name ?? 'Unknown Kit',
                    'count' => $record->occurrence_count,
                ];
            });
    }

    private function getOperatives(?Carbon $date, GetLeaderboardMetricsQuery $message): Collection
    {
        // 3. Shadow Operatives (Individual Superlatives)
        // We look for the "Iron Suits" - Troopers with the most attended records
        $query = EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->whereHas('event_shift.event', function ($q) use ($date) {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->whereHas('trooper', function ($q) {
                $q->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
                    // Exclude placeholder accounts.
                    ->where(Trooper::DISPLAY_NAME, '!=', 'Placeholder');
            })
            ->select(EventTrooper::TROOPER_ID, \DB::raw('count(*) as troop_count'))
            ->with(['trooper' => fn ($q) => $q->select(Trooper::ID, Trooper::DISPLAY_NAME)])
            ->groupBy(EventTrooper::TROOPER_ID);

        $this->applyOrgAttribution($query, $message->organization, []);

        return $query
            ->orderByDesc('troop_count')
            ->take($message->limit)
            ->get();
    }
}
