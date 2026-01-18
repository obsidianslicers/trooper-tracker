<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\OrganizationCostume;

/**
 * Handler for retrieving organizations with their costumes.
 *
 * Returns a mapped collection of organizations with nested costume data.
 * Each result contains the organization ID, name, and an array of costumes
 * with their IDs and names.
 *
 * Filters:
 * - Only organizations of type 'organization' (not regions or units)
 * - Optionally filters to specific organization IDs
 * - Orders organizations by name
 * - Orders costumes within each organization by name
 *
 * @implements QueryHandlerInterface<GetOrganizationCostumesQuery>
 */
readonly class GetOrganizationCostumesQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve organizations with costumes.
     *
     * @param GetOrganizationCostumesQuery $message The query with optional organization ID filter
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, organization_costumes: \Illuminate\Support\Collection}>
     */
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