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
        $fallback_org_options = $this->resolveFallbackOrgOptions($message->actor);

        return $this->resolveCandidateTroopers($message->actor, $message->trooper_id)
            ->flatMap(fn (Trooper $trooper) => $this->missingCreditRowsForTrooper($trooper, $message->actor, $fallback_org_options))
            ->values();
    }

    /**
     * Root/primary-club organizations the actor is authorized to credit, used as a fallback
     * picker when a shift has no automatically-eligible club. Reuses the same scope
     * (Organization::moderatedBy) already used elsewhere to bound what a moderator can touch —
     * returns every root org for administrators, or only the root orgs the actor themselves
     * moderates otherwise. Credit is always attributed at the root/primary-club level (see
     * EventTrooper::getPrimaryClub() usages), so this never offers a sub-org/unit.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function resolveFallbackOrgOptions(Trooper $actor): array
    {
        $organizations = Organization::query()
            ->whereNull(Organization::PARENT_ID)
            ->moderatedBy($actor)
            ->orderBy(Organization::NAME)
            ->get();

        return $this->mapOrgOptions($organizations);
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

    private function missingCreditRowsForTrooper(Trooper $trooper, Trooper $actor, array $fallback_org_options): Collection
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

        return $event_troopers
            ->filter(fn (EventTrooper $et) => empty($credited_ids_by_shift[$et->id] ?? []))
            ->map(fn (EventTrooper $et) => $this->buildRow($et, $trooper, $allowed_org_ids, $fallback_org_options))
            ->values();
    }

    private function buildRow(EventTrooper $event_trooper, Trooper $trooper, ?array $allowed_org_ids, array $fallback_org_options): array
    {
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
