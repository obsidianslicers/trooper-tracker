<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Support\Collection;

class GetOrganizationCostumesQuery
{
    public function __invoke(iterable $organization_ids = null): Collection
    {
        $with = [
            'organization_costumes' => function ($q)
            {
                $q->orderBy(OrganizationCostume::NAME);
            }
        ];

        $q = Organization::with($with)
            ->ofTypeOrganizations()
            ->orderBy(Organization::NAME);

        if ($organization_ids !== null)
        {
            $q->whereIn(Organization::ID, $organization_ids);
        }

        $organizations = $q->get();

        return $organizations->map(fn($org) => [
            'id' => $org->id,
            'name' => $org->name,
            'organization_costumes' => $org->organization_costumes->map(fn($costume) => [
                'id' => $costume->id,
                'name' => $costume->name,
            ]),
        ]);
    }
}
