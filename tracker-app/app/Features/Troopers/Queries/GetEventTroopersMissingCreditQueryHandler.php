<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Support\Collection;

/**
 * @implements QueryHandlerInterface<GetEventTroopersMissingCreditQuery>
 */
readonly class GetEventTroopersMissingCreditQueryHandler implements QueryHandlerInterface
{
    use HasOrgCreditAnnotation;

    public function __invoke(object $message): Collection
    {
        return $this->resolveCandidateTroopers($message->actor, $message->trooper_id)
            ->flatMap(fn (Trooper $trooper) => $this->missingCreditRowsForTrooper($trooper, $message->actor))
            ->values();
    }

    /**
     * Root/primary-club organizations to offer as a fallback when a shift has no automatically
     * eligible club. Must be limited to clubs the trooper is *currently* a member of
     * (tt_trooper_organizations) — HasOrgCreditAnnotation can only ever resolve a credited org to
     * a visible badge if it's in that list, so offering any other club (even ones the actor is
     * otherwise authorized to credit) would look like it worked but silently never display — the
     * exact failure mode this tool exists to fix. Also bounded by the acting mod/admin's own
     * authority via Organization::moderatedBy, same as everywhere else in this feature.
     *
     * @param  Collection<int, Organization>  $organizations  The trooper's current club memberships.
     * @return array<int, array{id: int, name: string}>
     */
    private function resolveFallbackOrgOptions(Collection $organizations, Trooper $actor): array
    {
        $root_org_ids = $organizations
            ->map(fn (Organization $org) => $org->getPrimaryClub()->id)
            ->unique()
            ->values()
            ->all();

        if (empty($root_org_ids))
        {
            return [];
        }

        $fallback_orgs = Organization::whereIn(Organization::ID, $root_org_ids)
            ->moderatedBy($actor)
            ->orderBy(Organization::NAME)
            ->get();

        return $this->mapOrgOptions($fallback_orgs);
    }

    private function resolveCandidateTroopers(Trooper $actor, ?int $trooper_id): Collection
    {
        if ($trooper_id !== null)
        {
            return Trooper::where(Trooper::ID, $trooper_id)->get();
        }

        $query = Trooper::query()
            ->whereHas('event_troopers', fn ($q) => $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value));

        if (!$actor->is_administrator)
        {
            $query = $query->moderatedBy($actor);
        }

        return $query->orderBy(Trooper::DISPLAY_NAME)->get();
    }

    private function missingCreditRowsForTrooper(Trooper $trooper, Trooper $actor): Collection
    {
        $event_troopers = EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->with(['event_shift.event', 'costume'])
            ->get();

        if ($event_troopers->isEmpty())
        {
            return collect();
        }

        $organizations = $trooper->organizations()
            ->wherePivotNull(TrooperOrganization::DELETED_AT)
            ->orderBy(Organization::NAME)
            ->get();

        $recent_shifts = $event_troopers->map(fn (EventTrooper $et) => (object) [
            'id' => $et->id,
            'event_trooper' => $et,
        ]);

        $candidate_orgs = $this->loadCandidateOrgs($recent_shifts);
        ['credited_ids_by_shift' => $credited_ids_by_shift] = $this->computeTroopCounts($recent_shifts, $organizations, $candidate_orgs);

        $allowed_org_ids = $actor->resolveModeratorOrgIds();
        $trooper_has_no_club_membership = $organizations->isEmpty();
        $fallback_org_options = $this->resolveFallbackOrgOptions($organizations, $actor);

        return $event_troopers
            ->filter(fn (EventTrooper $et) => empty($credited_ids_by_shift[$et->id] ?? []))
            ->map(fn (EventTrooper $et) => $this->buildRow($et, $trooper, $allowed_org_ids, $fallback_org_options, $trooper_has_no_club_membership))
            ->values();
    }

    private function buildRow(
        EventTrooper $event_trooper,
        Trooper $trooper,
        ?array $allowed_org_ids,
        array $fallback_org_options,
        bool $trooper_has_no_club_membership
    ): array {
        $org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids, $event_trooper->costume);
        $has_orphaned_db_value = $event_trooper->organization_id !== null || !empty($event_trooper->costume_organization_ids);

        return [
            'event_trooper_id' => $event_trooper->id,
            'trooper_id' => $trooper->id,
            'trooper_name' => $trooper->display_name,
            'event_id' => $event_trooper->event_shift->event->id,
            'event_name' => $event_trooper->event_shift->event->name,
            'shift_label' => $event_trooper->event_shift->time_display,
            'shift_starts_at' => $event_trooper->event_shift->shift_starts_at?->toIso8601String(),
            'costume_name' => $event_trooper->costume?->name,
            'org_options' => $this->mapOrgOptions($org_options),
            'has_eligible_options' => $org_options->isNotEmpty(),
            'has_orphaned_db_value' => $has_orphaned_db_value,
            'fallback_org_options' => $org_options->isEmpty() ? $fallback_org_options : [],
            // Credit can never render for this shift — regardless of what gets assigned — until
            // the trooper has at least one non-deleted tt_trooper_organizations row, since that's
            // the only list HasOrgCreditAnnotation matches a credited org against for display.
            'trooper_has_no_club_membership' => $trooper_has_no_club_membership,
        ];
    }

    /** @param  Collection<int, Organization>  $organizations */
    private function mapOrgOptions(Collection $organizations): array
    {
        return $organizations
            ->map(fn (Organization $org) => ['id' => $org->id, 'name' => $org->name])
            ->values()
            ->all();
    }
}
