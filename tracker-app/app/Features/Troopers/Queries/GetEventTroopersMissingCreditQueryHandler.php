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

        return $event_troopers
            ->filter(fn (EventTrooper $et) => empty($credited_ids_by_shift[$et->id] ?? []))
            ->map(fn (EventTrooper $et) => $this->buildRow($et, $trooper, $actor, $allowed_org_ids))
            ->values();
    }

    private function buildRow(EventTrooper $event_trooper, Trooper $trooper, Trooper $actor, ?array $allowed_org_ids): array
    {
        $org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids, $event_trooper->costume);
        $has_orphaned_db_value = $event_trooper->organization_id !== null || !empty($event_trooper->costume_organization_ids);

        $row = [
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
            'all_org_options' => null,
        ];

        if ($org_options->isEmpty() && $actor->is_administrator)
        {
            $all_orgs = Organization::whereIn(Organization::ID, $trooper->activeAssignmentOrganizationIds())
                ->orderBy(Organization::NAME)
                ->get();

            $row['all_org_options'] = $this->mapOrgOptions($all_orgs);
        }

        return $row;
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
