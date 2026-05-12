<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\AchievementType;
use App\Enums\EventTrooperStatus;
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
use App\Models\EventTrooper;
use App\Models\TrooperDonation;
use Carbon\Carbon;
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

    private function loadCandidateOrgs(Collection $recent_shifts): Collection
    {
        $candidate_org_ids = $recent_shifts->flatMap(function ($shift) {
            if (!$shift->event_trooper)
            {
                return [];
            }

            return array_merge(
                array_filter([$shift->event_trooper->organization_id]),
                $shift->event_trooper->costume_organization_ids ?? []
            );
        })->unique()->values()->toArray();

        return $candidate_org_ids
            ? Organization::whereIn(Organization::ID, $candidate_org_ids)
                ->get([Organization::ID, Organization::NODE_PATH])
                ->keyBy(Organization::ID)
            : collect();
    }

    private function computeTroopCounts(
        Collection $recent_shifts,
        Collection $organizations,
        Collection $candidate_orgs
    ): array {
        $troop_counts = [];
        $credited_ids_by_shift = [];

        foreach ($recent_shifts as $shift)
        {
            if ($shift->event_trooper?->status !== EventTrooperStatus::ATTENDED)
            {
                continue;
            }

            $et = $shift->event_trooper;
            $credited_ids = $et->organization_id !== null
                ? $this->creditByExplicitOrg($et, $shift->shift_starts_at, $organizations, $candidate_orgs)
                : $this->creditByCostumeOrgs($et, $shift->shift_starts_at, $organizations, $candidate_orgs);

            foreach ($credited_ids as $org_id)
            {
                $troop_counts[$org_id] = ($troop_counts[$org_id] ?? 0) + 1;
            }
            $credited_ids_by_shift[$shift->id] = $credited_ids;
        }

        return ['troop_counts' => $troop_counts, 'credited_ids_by_shift' => $credited_ids_by_shift];
    }

    private function creditByExplicitOrg(
        EventTrooper $et,
        Carbon $shift_date,
        Collection $organizations,
        Collection $candidate_orgs
    ): array {
        // Explicit org chosen — credit only the one trooper-org that contains it
        $match_org = $candidate_orgs->get($et->organization_id);
        if (!$match_org)
        {
            return [];
        }

        foreach ($organizations as $org)
        {
            if (str_starts_with($match_org->node_path, $org->node_path)
                && $this->wasMemberAt($org, $shift_date))
            {
                return [$org->id];
            }
        }

        return [];
    }

    private function creditByCostumeOrgs(
        EventTrooper $et,
        Carbon $shift_date,
        Collection $organizations,
        Collection $candidate_orgs
    ): array {
        $costume_node_paths = collect($et->costume_organization_ids ?? [])
            ->map(fn ($id) => $candidate_orgs->get($id)?->node_path)
            ->filter()
            ->values()
            ->toArray();

        if (empty($costume_node_paths))
        {
            return []; // Nothing checked — unattached
        }

        $credited = [];
        foreach ($organizations as $org)
        {
            if (!$this->wasMemberAt($org, $shift_date))
            {
                continue;
            }
            foreach ($costume_node_paths as $node_path)
            {
                if (str_starts_with($node_path, $org->node_path))
                {
                    $credited[] = $org->id;
                    break; // don't double-count same org from multiple costume orgs
                }
            }
        }

        return $credited; // Empty means unattached (checked org not in trooper's memberships)
    }

    private function annotateShiftsWithCreditedOrgNames(
        Collection $recent_shifts,
        Collection $organizations,
        array $credited_ids_by_shift
    ): void {
        $all_credited_ids = array_unique(array_merge(...(array_values($credited_ids_by_shift) ?: [[]])));
        $root_org_ids = collect($all_credited_ids)
            ->map(fn ($id) => $organizations->find($id)?->node_path)
            ->filter()
            ->map(fn ($np) => (int) explode(':', $np)[0])
            ->unique()
            ->all();

        $root_org_names = $root_org_ids
            ? Organization::whereIn(Organization::ID, $root_org_ids)
                ->pluck(Organization::NAME, Organization::ID)
            : collect();

        foreach ($recent_shifts as $shift)
        {
            $et = $shift->event_trooper;
            if (!$et)
            {
                continue;
            }

            // Always initialize so the view can safely check this property on any shift
            $credited_ids = $credited_ids_by_shift[$shift->id] ?? [];
            $et->credited_org_names = collect($credited_ids)
                ->map(fn ($id) => $organizations->find($id)?->node_path)
                ->filter()
                ->map(fn ($np) => $root_org_names[(int) explode(':', $np)[0]] ?? null)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }
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

    private function wasMemberAt(Organization $org, Carbon $shift_date): bool
    {
        $join_date = $org->pivot->join_date ?? null;

        return $join_date === null || $join_date <= $shift_date;
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
