<?php

declare(strict_types=1);

namespace App\Features\Changes\Queries;

use App\Models\Trooper;
use Carbon\Carbon;

/**
 * Query to retrieve model change history for a trooper.
 *
 * Returns ModelChange records for a trooper, including direct changes to the Trooper
 * model and changes to related EventTrooper records. Supports lookback filtering by
 * days (int), date string, or Carbon instance.
 *
 * @see GetModelChangesForTrooperQueryHandler
 */
readonly class GetModelChangesForTrooperQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper whose change history to retrieve.
     * @param int|string|Carbon $lookback Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        public readonly Trooper $trooper,
        public readonly int|string|Carbon $lookback,
    ) {
    }
}