<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving all organizations of type 'organization'.
 *
 * Filters organizations using the ofTypeOrganizations() scope to exclude
 * regions and units from the results. Returns organizations ordered by name.
 *
 * @implements QueryHandlerInterface<GetOrganizationsQuery>
 */
readonly class GetOrganizationsQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve all organizations.
     *
     * @param  GetOrganizationsQuery  $message  The query (no parameters)
     * @return Collection<int, Organization> Collection of organizations
     */
    public function __invoke(object $message): mixed
    {
        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        return $organizations;
    }
}
