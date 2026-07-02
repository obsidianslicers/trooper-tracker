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
use App\Models\EventUpload;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperDonation;
use Illuminate\Support\Collection;

readonly class GetTrooperServiceRecordQueryHandler implements QueryHandlerInterface
{
    use HasEventDisplayAssembler;
    use HasOrgCreditAnnotation;

    public function __construct()
    {
        $this->bootHasEventDisplayAssembler();
    }

    public function __invoke(object $message): mixed
    {
        $trooper = Trooper::with('trooper_achievements.organization')->findOrFail($message->trooper_id);

        $recent_shifts = $this->getRecentEventShifts($trooper);

        return [
            'trooper' => $trooper,
            'trooper_organizations' => $this->getOrganizations($trooper, $recent_shifts),
            'tagged_uploads' => $this->getTaggedUploads($trooper),
            'service_summary' => $this->getServiceSummary($trooper),
            'upcoming_shifts' => $this->getUpcomingEventShifts($trooper),
            'recent_shifts' => $recent_shifts,
            'all_donations' => $this->getAllDonations($trooper),
            'awards' => $this->getAwards($trooper),
        ];
    }

    private function getOrganizations(Trooper $trooper, Collection $recent_shifts): Collection
    {
        $organizations = $trooper->organizations()
            ->orderBy(Organization::NAME)
            ->get();

        $assignments = $trooper->trooper_assignments()
            ->with('organization')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        $candidate_orgs = $this->loadCandidateOrgs($recent_shifts);
        ['troop_counts' => $troop_counts, 'credited_ids_by_shift' => $credited_ids_by_shift]
            = $this->computeTroopCounts($recent_shifts, $organizations, $candidate_orgs);

        foreach ($organizations as $organization)
        {
            $organization->troop_count = $troop_counts[$organization->id] ?? 0;

            foreach ($assignments as $assignment)
            {
                if (str_starts_with($assignment->organization->node_path, $organization->node_path))
                {
                    $organization->assignment = $assignment;
                }
            }
        }

        $this->annotateShiftsWithCreditedOrgNames($recent_shifts, $organizations, $credited_ids_by_shift);

        return $organizations;
    }

    private function getTaggedUploads(Trooper $trooper): Collection
    {
        return EventUpload::byTrooper($trooper->id)->get();
    }

    private function getServiceSummary(Trooper $trooper): array
    {
        return [
            'total_shifts' => $trooper->getAchievementValue(AchievementType::TROOPER_SHIFTS),
            'total_hours' => $trooper->getAchievementValue(AchievementType::VOLUNTEER_HOURS),
            'rank' => $trooper->getAchievementValue(AchievementType::TROOPER_RANK),
            'rank_title' => $trooper->getTitleByTrooperRank(),
            'rank_theme' => $trooper->getRankTheme(),
            'direct_funds' => $trooper->getAchievementValue(AchievementType::DIRECT_FUNDS),
            'indirect_funds' => $trooper->getAchievementValue(AchievementType::INDIRECT_FUNDS),
            'milestones' => $this->buildEarnedMilestones($trooper),
            'donation_months' => $trooper->getAchievementValue(AchievementType::DONATION_MONTHS),
            'total_donated' => $trooper->getAchievementValue(AchievementType::TOTAL_DONATED) ?? $this->computeTotalDonated($trooper),
        ];
    }

    private function buildEarnedMilestones(Trooper $trooper): array
    {
        return $trooper->trooper_achievements
            ->filter(fn ($achievement) => $achievement->type->isMilestone() && (bool) $achievement->value)
            ->sortByDesc(fn ($achievement) => $achievement->display_order)
            ->map(fn ($achievement) => [
                'type' => $achievement->type,
                'title' => $achievement->type->toTitle(),
                'description' => $achievement->display_description,
                'icon' => $achievement->type->toIcon(),
                'is_earned' => true,
            ])
            ->values()
            ->toArray();
    }

    private function getUpcomingEventShifts(Trooper $trooper): Collection
    {
        $shifts = EventShift::with($this->buildEventShiftRelations())
            ->byTrooper($trooper->id, false)
            ->where(EventShift::SHIFT_STARTS_AT, '>', now()->subYear(1))
            ->orderBy(EventShift::SHIFT_STARTS_AT)
            ->get();

        $shifts->each(fn ($shift) => $this->transformEventShift($shift));
        $shifts->each(fn ($shift) => $shift->event_trooper = $shift->event_troopers->first());

        return $shifts;
    }

    private function getRecentEventShifts(Trooper $trooper): Collection
    {
        $shifts = EventShift::with($this->buildEventShiftRelations())
            ->byTrooper($trooper->id, true)
            ->where(EventShift::SHIFT_ENDS_AT, '<=', now())
            ->orderByDesc(EventShift::SHIFT_STARTS_AT)
            ->get();

        $shifts->each(fn ($shift) => $this->transformEventShift($shift));
        $shifts->each(fn ($shift) => $shift->event_trooper = $shift->event_troopers->first());

        return $shifts;
    }

    private function getAllDonations(Trooper $trooper): Collection
    {
        return TrooperDonation::byTrooper($trooper->id)
            ->orderByDesc(TrooperDonation::CREATED_AT)
            ->get();
    }

    // Fallback for troopers whose TOTAL_DONATED achievement has not yet been calculated.
    private function computeTotalDonated(Trooper $trooper): float
    {
        return (float) TrooperDonation::byTrooper($trooper->id)->sum(TrooperDonation::AMOUNT);
    }

    private function getAwards(Trooper $trooper): Collection
    {
        return AwardTrooper::byTrooper($trooper->id)->get();
    }

    private function buildEventShiftRelations(): array
    {
        return array_merge([
            'event.organization',
            'event:'.implode(',', [Event::ID, Event::NAME, Event::ORGANIZATION_ID]),
        ], $this->buildTrooperCostumeRelations());
    }

    private function buildTrooperCostumeRelations(): array
    {
        return [
            'event_troopers.trooper:'.implode(',', [Trooper::ID, Trooper::DISPLAY_NAME]),
            'event_troopers.trooper.trooper_costumes:'.implode(',', [
                TrooperCostume::ID, TrooperCostume::TROOPER_ID, TrooperCostume::ORGANIZATION_COSTUME_ID,
            ]),
            'event_troopers.trooper.trooper_costumes.organization_costume:'.implode(',', [
                OrganizationCostume::ID, OrganizationCostume::COSTUME_ID, OrganizationCostume::ORGANIZATION_ID,
            ]),
            'event_troopers.costume:'.implode(',', [Costume::ID, Costume::NAME]),
            'event_troopers.backup_costume:'.implode(',', [Costume::ID, Costume::NAME]),
        ];
    }
}
