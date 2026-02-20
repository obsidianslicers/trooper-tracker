<?php

declare(strict_types=1);

namespace App\Features\Costumes\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;

/**
 * Handler for retrieving the organization hierarchy for costumes.
 *
 * @implements QueryHandlerInterface<GetCostumesPickerQuery>
 */
readonly class GetCostumesPickerQueryHandler implements QueryHandlerInterface
{
    /**
     * Handle the query to retrieve costumes with their associated organization hierarchy.
     *
     * @param GetCostumesPickerQuery $message The query containing trooper and filter criteria
     * @return \Illuminate\Support\Collection<int, Costume> Collection of organizations with nested hierarchy.
     */
    public function __invoke(object $message): mixed
    {
        $organization_costumes_relation = 'organization_costumes:'
            . OrganizationCostume::ID . ','
            . OrganizationCostume::ORGANIZATION_ID . ','
            . OrganizationCostume::COSTUME_ID . ','
            . OrganizationCostume::PREFIX;

        $with = [
            $organization_costumes_relation,
            'organization_costumes.organization:' . Organization::ID . ',' . Organization::NAME
        ];

        $with['organization_costumes'] = function ($query) use ($message)
        {
            $query->whereHas('organization', fn($q) => $q->whereIn(Organization::ID, $message->organization_ids));
        };


        return Costume::with($with)
            ->orderBy(Costume::NAME)
            ->select([Costume::ID, Costume::NAME,])
            ->get();
    }
}