<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve a trooper's organization assignments.
 *
 * Returns all organizations with assignment information indicating
 * which specific organization (organization, region, or unit) the trooper
 * is a member of within each organization hierarchy.
 *
 * @see GetTrooperAssignmentsQueryHandler
 */
readonly class GetTrooperAdministratorsQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper whose assignments to retrieve
     */
    public function __construct()
    {
    }
}