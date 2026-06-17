<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Support\Collection;

trait HasOrgCreditAnnotation
{
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
            $credited_ids = !empty($et->costume_organization_ids)
                ? $this->creditByCostumeOrgs($et, $organizations, $candidate_orgs)
                : $this->creditByExplicitOrg($et, $organizations, $candidate_orgs);

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
        Collection $organizations,
        Collection $candidate_orgs
    ): array {
        $match_org = $candidate_orgs->get($et->organization_id);
        if (!$match_org)
        {
            return [];
        }

        foreach ($organizations as $org)
        {
            if (str_starts_with($match_org->node_path, $org->node_path))
            {
                return [$org->id];
            }
        }

        return [];
    }

    private function creditByCostumeOrgs(
        EventTrooper $et,
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
            return [];
        }

        $credited = [];
        foreach ($organizations as $org)
        {
            foreach ($costume_node_paths as $node_path)
            {
                if (str_starts_with($node_path, $org->node_path))
                {
                    $credited[] = $org->id;
                    break; // don't double-count same org from multiple costume orgs
                }
            }
        }

        return $credited;
    }

    private function resolveRootOrgNames(array $all_credited_ids, Collection $organizations): Collection
    {
        $root_org_ids = collect($all_credited_ids)
            ->map(fn ($id) => $organizations->find($id)?->node_path)
            ->filter()
            ->map(fn ($np) => (int) explode(':', $np)[0])
            ->unique()
            ->all();

        return $root_org_ids
            ? Organization::whereIn(Organization::ID, $root_org_ids)
                ->pluck(Organization::NAME, Organization::ID)
            : collect();
    }

    private function annotateShiftsWithCreditedOrgNames(
        Collection $recent_shifts,
        Collection $organizations,
        array $credited_ids_by_shift
    ): void {
        $all_credited_ids = array_unique(array_merge(...(array_values($credited_ids_by_shift) ?: [[]])));
        $root_org_names = $this->resolveRootOrgNames($all_credited_ids, $organizations);

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
}
