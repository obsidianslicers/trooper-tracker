<?php

declare(strict_types=1);

namespace App\Features\Notices\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve all notices visible to a trooper.
 *
 * Returns a collection of notices that the trooper has permission to view,
 * ordered by the notice's starts_at timestamp.
 *
 * @see GetTrooperNoticesQueryHandler
 */
readonly class GetTrooperNoticesQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper requesting their visible notices
     */
    public function __construct(public readonly Trooper $trooper)
    {
    }
}