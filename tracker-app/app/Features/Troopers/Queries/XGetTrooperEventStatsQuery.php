<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

/**
 * Query to retrieve aggregated event statistics for all troopers.
 *
 * Returns a collection of trooper event data including total troops,
 * volunteer hours, and funds raised. Used for calculating achievements.
 *
 * @see GetTrooperEventStatsQueryHandler
 */
readonly class GetTrooperEventStatsQuery
{
    /**
     * Create a new query instance.
     *
     * No parameters required - retrieves stats for all troopers.
     */
    public function __construct()
    {
    }
}
