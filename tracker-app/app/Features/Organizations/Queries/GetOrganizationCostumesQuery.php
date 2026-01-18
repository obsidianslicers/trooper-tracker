<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

/**
 * Query to retrieve organizations with their associated costumes.
 *
 * Returns organizations of type 'organization' with their related organization_costumes.
 * Optionally filters to specific organizations by IDs.
 *
 * @see GetOrganizationCostumesQueryHandler
 */
readonly class GetOrganizationCostumesQuery
{
    /**
     * Create a new query instance.
     *
     * @param iterable<int>|null $organization_ids Optional list of organization IDs to filter by
     */
    public function __construct(public readonly ?iterable $organization_ids = null)
    {
    }
}