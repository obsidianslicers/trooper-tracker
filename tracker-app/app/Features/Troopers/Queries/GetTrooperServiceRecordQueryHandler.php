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

/**
 * Handler for retrieving a trooper's complete service record.
 *
 * Processes GetTrooperServiceRecordQuery to return comprehensive trooper data including:
 * - Organizations the trooper belongs to
 * - Approved costumes with organization associations
 * - Service summary (shifts, hours, rank, funds, milestones)
 * - Upcoming and recent event shifts
 * - Recent donations
 * - Awards received
 *
 * All data is formatted for display in the trooper's service record page.
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
     * Handle the query to retrieve a trooper's complete service record.
     *
     * Loads the trooper with achievements and returns an array containing:
     * - trooper: The Trooper model with trooper_achievements loaded
     * - trooper_organizations: Collection of organizations ordered by name
     * - trooper_costumes: Collection of approved costumes with organization display strings
     * - service_summary: Array with shifts, hours, rank, funds, and milestone data
     * - upcoming_shifts: EventShifts starting after 1 year ago, ordered by start time
     * - recent_shifts: EventShifts attended in past year, ordered by start time descending
     * - recent_donations: TrooperDonations from the past year
     * - awards: AwardTrooper records for the trooper
     *
     * @param  GetTrooperServiceRecordQuery  $message  The query containing the trooper_id
     * @return array Associative array with trooper service record data
     */
    public function __invoke(object $message): mixed
    {
        $trooper = Trooper::with('trooper_achievements')->findOrFail($message->trooper_id);

        return [
            'trooper' => $trooper,
            'trooper_organizations' => $this->getOrganizations($trooper),
            'tagged_uploads' => $this->getTaggedUploads($trooper),
            'service_summary' => $this->getServiceSummary($trooper),
            'upcoming_shifts' => $this->getUpcomingEventShifts($trooper),
            'recent_shifts' => $this->getRecentEventShifts($trooper),
            'all_donations' => $this->getAllDonations($trooper),
            'awards' => $this->getAwards($trooper),
        ];
    }

    private function getOrganizations(Trooper $trooper): Collection
    {
        $organizations = $trooper->organizations()
            ->orderBy(Organization::NAME)
            ->get();

        $assignments = $trooper->trooper_assignments()
            ->with('organization')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
            foreach ($assignments as $assignment)
            {
                if (str_starts_with($assignment->organization->node_path, $organization->node_path))
                {
                    $organization->assignment = $assignment;
                }
            }
        }

        return $organizations;
    }

    private function getTaggedUploads(Trooper $trooper): Collection
    {
        return EventUpload::byTrooper($trooper->id)->get();
    }

    private function getServiceSummary(Trooper $trooper): array
    {
        // Map over all AchievementTypes to find Milestones
        $milestones = collect(AchievementType::cases())
            ->filter(fn (AchievementType $type) => $type->isMilestone())
            ->map(function (AchievementType $type) use ($trooper) {
                // Get value from your existing trooper method
                $value = $trooper->getAchievementValue($type);

                return [
                    'type' => $type,
                    'title' => $type->toTitle(),
                    'description' => $type->toDescription(),
                    'icon' => $type->toIcon(),
                    'is_earned' => $value !== null && $value > 0,
                    // If your system tracks the date, you'd pull it here;
                    // otherwise, we'll assume 'Active' status
                ];
            })
            ->filter(fn ($milestone) => $milestone['is_earned'])
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
            'donation_months' => $trooper->getAchievementValue(AchievementType::DONATION_MONTHS),
            'total_donated' => $trooper->getAchievementValue(AchievementType::TOTAL_DONATED) ?? $this->computeTotalDonated($trooper),
        ];
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
            ->where(EventShift::SHIFT_STARTS_AT, '>', now()->subYear(1))
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

    /**
     * Fallback for troopers whose TOTAL_DONATED achievement has not yet been
     * computed by the rank command. Can be removed once all troopers have been
     * processed by `tracker:calculate-trooper-achievements`.
     */
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
            'event:'.implode(',', $event_columns),
            'event_troopers.trooper:'.implode(',', $trooper_columns),
            'event_troopers.trooper.trooper_costumes:'.implode(',', $trooper_costume_columns),
            'event_troopers.trooper.trooper_costumes.organization_costume:'.implode(',', $organization_costume_columns),
            'event_troopers.costume:'.implode(',', $costume_columns),
            'event_troopers.backup_costume:'.implode(',', $costume_columns),
        ];

        return $with;
    }
}
