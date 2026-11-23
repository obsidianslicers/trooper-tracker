<?php

declare(strict_types=1);

namespace App\Messages\Concerns;

use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns a report's query-string filters into the values its query takes.
 */
trait ResolvesReportFilters
{
    protected function startOfDay(?string $date): ?Carbon
    {
        return filled($date) ? Carbon::parse($date)->startOfDay() : null;
    }

    protected function endOfDay(?string $date): ?Carbon
    {
        return filled($date) ? Carbon::parse($date)->endOfDay() : null;
    }

    /**
     * @param  array<int, string>  $columns
     * @return Collection<int, Organization>
     */
    protected function moderatedOrganizations(Trooper $moderator, string $order_by, array $columns): Collection
    {
        return Organization::moderatedBy($moderator)
            ->orderBy($order_by)
            ->get($columns);
    }

    /**
     * The organization picked in the filter, when it is one the moderator may see.
     *
     * @param  Collection<int, Organization>  $organizations
     */
    protected function chosenOrganization(Collection $organizations, ?int $organization_id): ?Organization
    {
        return $organization_id ? $organizations->firstWhere('id', $organization_id) : null;
    }

    /**
     * The id of the top-level club each organization belongs to.
     *
     * @param  Collection<int, Organization>  $organizations
     * @return array<int, int>
     */
    protected function rootOrganizationIds(Collection $organizations): array
    {
        return $organizations
            ->map(fn (Organization $org): int => (int) explode(Organization::NODE_PATH_SEP, $org->node_path)[0])
            ->unique()
            ->values()
            ->all();
    }
}
