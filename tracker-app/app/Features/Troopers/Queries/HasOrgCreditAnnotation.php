<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Resolves which club(s) each attended shift was credited to.
 *
 * Credit is historical: it resolves from the credited organization's place in the org tree
 * (its root club), never from the trooper's current memberships. A trooper who later leaves
 * a club keeps every troop that was credited to it.
 */
trait HasOrgCreditAnnotation
{
    private function loadCandidateOrgs(Collection $recent_shifts): Collection
    {
        $candidate_org_ids = $recent_shifts->flatMap(function ($shift)
        {
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

    /**
     * Counts and credited ids are both expressed as root club ids.
     *
     * @return array{
     *     troop_counts: array<int, int>,
     *     credited_ids_by_shift: array<int, array<int, int>>
     * }
     */
    private function computeTroopCounts(
        Collection $recent_shifts,
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

            $credited_ids = $this->resolveCreditedRootOrgIds(
                $shift->event_trooper,
                $candidate_orgs
            );

            foreach ($credited_ids as $org_id)
            {
                $troop_counts[$org_id] = ($troop_counts[$org_id] ?? 0) + 1;
            }
            $credited_ids_by_shift[$shift->id] = $credited_ids;
        }

        return ['troop_counts' => $troop_counts, 'credited_ids_by_shift' => $credited_ids_by_shift];
    }

    /**
     * Maps a shift's credited orgs (costume_organization_ids, else organization_id) to their
     * distinct root club ids. Same rule as Organization::rootIdsFor, applied to the
     * already-loaded candidate orgs to avoid a query per shift.
     *
     * @return array<int, int>
     */
    private function resolveCreditedRootOrgIds(EventTrooper $et, Collection $candidate_orgs): array
    {
        $credited_org_ids = !empty($et->costume_organization_ids)
            ? $et->costume_organization_ids
            : array_filter([$et->organization_id]);

        return collect($credited_org_ids)
            ->map(fn ($id) => $candidate_orgs->get($id)?->node_path)
            ->filter()
            ->map(fn ($np) => (int) Str::before($np, Organization::NODE_PATH_SEP))
            ->unique()
            ->values()
            ->all();
    }

    /** @return Collection<int, string> Root club names keyed by id. */
    private function resolveRootOrgNames(array $root_org_ids): Collection
    {
        return $root_org_ids
            ? Organization::whereIn(Organization::ID, $root_org_ids)
                ->pluck(Organization::NAME, Organization::ID)
            : collect();
    }

    private function annotateShiftsWithCreditedOrgNames(
        Collection $recent_shifts,
        array $credited_ids_by_shift
    ): void {
        $all_credited_ids = array_unique(array_merge(...(array_values($credited_ids_by_shift) ?: [[]])));
        $root_org_names = $this->resolveRootOrgNames($all_credited_ids);

        foreach ($recent_shifts as $shift)
        {
            $et = $shift->event_trooper;
            if (!$et)
            {
                continue;
            }

            // Always initialize so the view can safely check this property on any shift
            $et->credited_org_names = collect($credited_ids_by_shift[$shift->id] ?? [])
                ->map(fn ($id) => $root_org_names[$id] ?? null)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }
    }
}
