<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Models\OrganizationCostume;
use App\Models\Trooper;

class GetTrooperCostumesForOrganizationQuery
{
    public function __invoke(int $trooperId, int $organization_id)
    {
        $trooper = Trooper::with('trooper_costumes.organization_costume.organization')
            ->findOrFail($trooperId);

        $assignedIds = $trooper->trooper_costumes
            ->filter(fn($tc) => $tc->organization_costume?->organization_id === $organization_id)
            ->pluck('organization_costume.id')
            ->filter()
            ->values();

        return OrganizationCostume::with('organization')
            ->where(OrganizationCostume::ORGANIZATION_ID, $organization_id)
            ->excluding($assignedIds)
            ->orderBy(OrganizationCostume::NAME)
            ->toOptions(OrganizationCostume::NAME, OrganizationCostume::ID);
    }
}
