<?php

declare(strict_types=1);

namespace App\Features\Costumes\Queries;

/**
 * Query to retrieve the complete organization hierarchy.
 *
 * Returns all organizations in a hierarchical structure (organizations, regions, and units)
 * with nested relationships. Optionally filters to a specific organization and its descendants.
 *
 * @see GetCostumesPickerQueryHandler
 */
readonly class GetCostumesPickerQuery
{
    /**
     * Create a new query instance.
     *
     * @param  int[]|null  $organization_ids  Optional array of organization IDs to filter hierarchy to specific orgs and descendants
     */
    public function __construct(public readonly array $organization_ids) {}
}
