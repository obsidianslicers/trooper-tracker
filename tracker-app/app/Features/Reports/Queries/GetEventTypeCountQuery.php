<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Features\Reports\Concerns\HasLookback;
use App\Models\Trooper;
use Carbon\Carbon;

/**
 * Query to retrieve event statistics grouped by event type.
 *
 * Returns counts and trooper participation metrics for each event type
 * within the specified lookback period.
 *
 * @see GetEventTypeCountQueryHandler
 */
readonly class GetEventTypeCountQuery
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $moderator  The moderator whose events to analyze.
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        public readonly Trooper $moderator,
        public readonly int|string|Carbon $lookback,
    ) {}
}
