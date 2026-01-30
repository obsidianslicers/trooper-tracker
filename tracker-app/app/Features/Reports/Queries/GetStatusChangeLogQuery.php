<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Features\Reports\Concerns\HasLookback;
use App\Models\Trooper;
use Carbon\Carbon;

/**
 * Query to retrieve event trooper status change log.
 *
 * Returns EventTrooper records that were marked as ATTENDED within the
 * lookback period, excluding self-updates.
 *
 * @see GetStatusChangeLogQueryHandler
 */
readonly class GetStatusChangeLogQuery
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $moderator  The moderator whose managed troopers to track.
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        public readonly Trooper $moderator,
        public readonly int|string|Carbon $lookback,
    ) {
    }
}
