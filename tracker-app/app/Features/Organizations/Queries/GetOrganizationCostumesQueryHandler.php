<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\OrganizationCostume;


readonly class GetOrganizationCostumesQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        /** @var GetOrganizationCostumesQuery $message */
        $with = [
            'organization_costumes' => function ($q)
            {
                $q->orderBy(OrganizationCostume::NAME);
            }
        ];

        $q = Organization::with($with)
            ->ofTypeOrganizations()
            ->orderBy(Organization::NAME);

        if ($message->organization_ids !== null)
        {
            $q->whereIn(Organization::ID, $message->organization_ids);
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