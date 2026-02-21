<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\AchievementType;
use App\Features\Events\Queries\HasEventDisplayAssembler;
use App\Models\AwardTrooper;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\EventTrooper;
use App\Models\TrooperCostume;
use App\Models\TrooperDonation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handler for retrieving troopers for picker/dropdown components.
 *
 * Processes GetTrooperServiceRecordQuery to return troopers based on:
 * - Organization filtering: Returns only troopers belonging to a specific organization
 * - Filter criteria: Applies search term and role filtering via TrooperFilter
 *
 * All results are ordered by trooper name for consistent UI display.
 *
 * @implements QueryHandlerInterface<GetTrooperServiceRecordQuery>
 */
readonly class GetTrooperServiceRecordQueryHandler implements QueryHandlerInterface
{
    use HasEventDisplayAssembler;

    public function __construct()
    {
        $this->bootHasEventDisplayAssembler();
    }

    /**
     * Handle the query to retrieve troopers for picker components.
     *
     * Query behavior:
     * 1. Start with active troopers who have completed setup, ordered by name
     * 2. If organization_id is set: Filter to troopers belonging to that organization
     * 3. If moderated_only is set: Filter to troopers moderated by the requesting trooper
     * 4. If filter has criteria: Apply search term and role filtering and return results
     * 5. If no filter criteria: Return empty collection
     *
     * @param  GetTrooperServiceRecordQuery  $message  The query containing filter and scope criteria
     * @return array Collection of filtered troopers, or empty if no filter applied
     */
    public function __invoke(object $message): mixed
    {
        $trooper = Trooper::with('trooper_achievements')->findOrFail($message->trooper_id);

        return [
            'trooper' => $trooper,
            'trooper_organizations' => $this->getOrganizations($trooper),
            'trooper_costumes' => $this->getCostumes($trooper),
            'service_summary' => $this->getServiceSummary($trooper),
            'upcoming_shifts' => $this->getUpcomingEventShifts($trooper),
            'recent_shifts' => $this->getRecentEventShifts($trooper),
            'recent_donations' => $this->getRecentDonations($trooper),
            'awards' => $this->getAwards($trooper),
        ];
    }

    private function getOrganizations(Trooper $trooper): Collection
    {
        $organizations = $trooper->organizations()
            ->orderBy(Organization::NAME)
            ->get();

        return $organizations;
    }

    private function getCostumes(Trooper $trooper): Collection
    {
        $costumes = Costume::forTrooper($trooper->id, null)
            ->orderBy(Costume::NAME)
            ->get()
            ->filter(fn($c) => !in_array($c->name, ['Command Staff', 'Handler']));

        // Transform for the final output
        $results = $costumes->each(function ($costume)
        {
            $names = $costume->organization_costumes
                ->map(fn($oc) => $oc->organization->name)
                ->sort()
                ->values();

            $prefix = $names->count() > 1 ? '(*) ' : '';
            $name_list = $names->isEmpty() ? '(unattached)' : $names->implode(', ');

            $costume->costume_organizations = "{$prefix}{$name_list}";
        });

        return $results;
    }

    private function getServiceSummary(Trooper $trooper): array
    {
        // Map over all AchievementTypes to find Milestones
        $milestones = collect(AchievementType::cases())
            ->filter(fn(AchievementType $type) => $type->isMilestone())
            ->map(function (AchievementType $type) use ($trooper)
            {
                // Get value from your existing trooper method
                $value = $trooper->getAchievementValue($type);

                return [
                    'type' => $type,
                    'title' => $type->toTitle(),
                    'icon' => $type->toIcon(),
                    'is_earned' => $value !== null && $value > 0,
                    // If your system tracks the date, you'd pull it here; 
                    // otherwise, we'll assume 'Active' status
                ];
            })
            ->filter(fn($milestone) => $milestone['is_earned'])
            ->reverse()
            ->values()
            ->toArray();

        return [
            'total_shifts' => $trooper->getAchievementValue(AchievementType::TROOPER_SHIFTS),
            'total_hours' => $trooper->getAchievementValue(AchievementType::VOLUNTEER_HOURS),
            'rank' => $trooper->getAchievementValue(AchievementType::TROOPER_RANK),
            'rank_title' => $trooper->getTitleByTrooperRank(),
            'rank_theme' => $trooper->getRankTheme(),
            'direct_funds' => $trooper->getAchievementValue(AchievementType::DIRECT_FUNDS),
            'indirect_funds' => $trooper->getAchievementValue(AchievementType::INDIRECT_FUNDS),
            'milestones' => $milestones,
        ];
    }

    private function getUpcomingEventShifts(Trooper $trooper): Collection
    {
        $shifts = EventShift::with($this->buildEventShiftRelations())
            ->byTrooper($trooper->id, false)
            ->where(EventShift::SHIFT_STARTS_AT, '>', now()->subYear(1))
            ->orderBy(EventShift::SHIFT_STARTS_AT)
            ->get();

        $shifts->each(fn($shift) => $this->transformEventShift($shift));
        $shifts->each(fn($shift) => $shift->event_trooper = $shift->event_troopers->first());

        return $shifts;
    }

    private function getRecentEventShifts(Trooper $trooper): Collection
    {
        $shifts = EventShift::with($this->buildEventShiftRelations())
            ->byTrooper($trooper->id, true)
            ->where(EventShift::SHIFT_STARTS_AT, '>', now()->subYear(1))
            ->orderByDesc(EventShift::SHIFT_STARTS_AT)
            ->get();

        $shifts->each(fn($shift) => $this->transformEventShift($shift));
        $shifts->each(fn($shift) => $shift->event_trooper = $shift->event_troopers->first());

        return $shifts;
    }

    private function getRecentDonations(Trooper $trooper): Collection
    {
        return TrooperDonation::byTrooper($trooper->id)
            ->where(TrooperDonation::CREATED_AT, '>', now()->subYear())
            ->get();
    }

    private function getAwards(Trooper $trooper): Collection
    {
        return AwardTrooper::byTrooper($trooper->id)->get();
    }

    private function buildEventShiftRelations(): array
    {
        $trooper_columns = [
            Trooper::ID,
            Trooper::DISPLAY_NAME,
        ];

        $trooper_costume_columns = [
            TrooperCostume::ID,
            TrooperCostume::TROOPER_ID,
            TrooperCostume::ORGANIZATION_COSTUME_ID,
        ];

        $organization_costume_columns = [
            OrganizationCostume::ID,
            OrganizationCostume::COSTUME_ID,
            OrganizationCostume::ORGANIZATION_ID,
        ];

        $costume_columns = [
            Costume::ID,
            Costume::NAME,
        ];

        $event_columns = [
            Event::ID,
            Event::NAME,
            Event::ORGANIZATION_ID,
        ];

        $with = [
            'event.organization',
            'event:' . implode(',', $event_columns),
            'event_troopers.trooper:' . implode(',', $trooper_columns),
            'event_troopers.trooper.trooper_costumes:' . implode(',', $trooper_costume_columns),
            'event_troopers.trooper.trooper_costumes.organization_costume:' . implode(',', $organization_costume_columns),
            'event_troopers.costume:' . implode(',', $costume_columns),
            'event_troopers.backup_costume:' . implode(',', $costume_columns),
        ];

        return $with;
    }
}