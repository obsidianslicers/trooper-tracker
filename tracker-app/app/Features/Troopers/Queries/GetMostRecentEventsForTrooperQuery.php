<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query for retrieving the most recent attended events for a trooper.
 *
 * Returns organizations with the most recent event shift where the trooper
 * attended (status = ATTENDED), grouped by organization.
 *
 * @see GetMostRecentEventsForTrooperQueryHandler
 */
readonly class GetMostRecentEventsForTrooperQuery
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper whose recent events to retrieve
     */
    public function __construct(public readonly Trooper $trooper) {}
}
