<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Filters\TrooperFilter;
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
readonly class GetTrooperNotificationsQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper requesting organizations
     * @param array $data Query parameters:
     *                    - 'moderated_only' (bool): Filter to moderated organizations only (default: false)
     *                    - 'organization_id' (int|null): Filter to specific org and descendants (default: null)
     */
    public function __construct(public readonly Trooper $trooper)
    {
    }
}