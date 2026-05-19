<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve organizations for use in picker/dropdown components.
 *
 * This query supports three modes:
 * 1. Moderated only: Returns only organizations the trooper moderates
 * 2. Specific organization: Returns an organization and all its descendants (via node_path)
 * 3. All organizations: Returns all organizations in sequence order
 *
 * The results are always ordered by the organization's sequence field.
 */
readonly class GetOrganizationsForPickerQuery
{
    /**
     * Whether to filter to only organizations moderated by the trooper.
     */
    public readonly bool $moderated_only;

    /**
     * Optional organization ID to filter by (includes descendants via node_path).
     */
    public readonly ?int $organization_id;

    /**
     * Optional maximum depth to include. When set, only orgs at depth <= depth_max are returned.
     */
    public readonly ?int $depth_max;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper requesting organizations
     * @param  array  $data  Query parameters:
     *                       - 'moderated_only' (bool): Filter to moderated organizations only (default: false)
     *                       - 'organization_id' (int|null): Filter to specific org and descendants (default: null)
     *                       - 'depth_max' (int|null): Maximum org depth to include (default: null = all depths)
     */
    public function __construct(public readonly Trooper $trooper, array $data)
    {
        $this->moderated_only = boolval($data['moderated_only'] ?? false);
        $this->organization_id = empty($data['organization_id']) ? null : intval($data['organization_id']);
        $this->depth_max = isset($data['depth_max']) && $data['depth_max'] !== null ? intval($data['depth_max']) : null;
    }
}
