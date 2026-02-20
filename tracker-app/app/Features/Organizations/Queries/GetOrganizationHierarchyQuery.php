<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

/**
 * Query to retrieve the complete organization hierarchy.
 *
 * Returns all organizations in a hierarchical structure (organizations, regions, and units)
 * with nested relationships. Optionally filters to a specific organization and its descendants.
 *
 * @see GetOrganizationHierarchyQueryHandler
 */
readonly class GetOrganizationHierarchyQuery
{
    /**
     * Create a new query instance.
     *
     * @param  int|null  $organization_id  Optional organization ID to filter hierarchy to specific org and descendants
     */
    public function __construct(public readonly ?int $organization_id = null) {}
}
