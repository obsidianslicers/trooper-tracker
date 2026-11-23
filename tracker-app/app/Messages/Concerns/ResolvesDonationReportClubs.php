<?php

declare(strict_types=1);

namespace App\Messages\Concerns;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * The clubs the Donation Event Summary can be filtered by, and which of them are picked.
 */
trait ResolvesDonationReportClubs
{
    use ResolvesReportFilters;

    /**
     * The filter offers the top-level clubs, or the moderator's units when they have only one
     * club. Picking none, or all of them, means no filter.
     *
     * @param  array<int, mixed>  $raw_ids
     * @return array{
     *     filter_orgs: Collection<int, Organization>,
     *     selected_ids: array<int, int>,
     *     selected_org_ids: array<int, int>,
     *     accessible_org_ids: array<int, int>
     * }
     */
    protected function donationClubs(Trooper $moderator, array $raw_ids): array
    {
        $organizations = $this->moderatedOrganizations(
            $moderator,
            Organization::SEQUENCE,
            ['id', 'name', 'depth', 'node_path'],
        );

        $top_level_orgs = $organizations->where('depth', 0)->values();
        $filter_orgs = $top_level_orgs->count() > 1
            ? $top_level_orgs
            : $organizations->where('depth', 1)->values();

        $filter_ids = $filter_orgs->pluck('id')->all();
        $selected_ids = array_values(array_intersect(array_map('intval', $raw_ids), $filter_ids));

        if (empty($selected_ids) || count($selected_ids) === count($filter_ids))
        {
            $selected_ids = [];
        }

        return [
            'filter_orgs' => $filter_orgs,
            'selected_ids' => $selected_ids,
            'selected_org_ids' => $this->withinSelected($organizations, $filter_orgs, $selected_ids),
            'accessible_org_ids' => $this->rootOrganizationIds($organizations),
        ];
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  Collection<int, Organization>  $filter_orgs
     * @param  array<int, int>  $selected_ids
     * @return array<int, int>
     */
    private function withinSelected(Collection $organizations, Collection $filter_orgs, array $selected_ids): array
    {
        if (empty($selected_ids))
        {
            return [];
        }

        $node_paths = $filter_orgs->whereIn('id', $selected_ids)->pluck('node_path');

        return $organizations
            ->filter(fn (Organization $org): bool => $node_paths
                ->contains(fn (string $path): bool => str_starts_with($org->node_path, $path)))
            ->pluck('id')
            ->values()
            ->all();
    }
}
