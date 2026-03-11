<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Features\Concerns\HasLookback;
use App\Models\Trooper;
use Carbon\Carbon;

/**
 * Query to retrieve troopers without recent event activity.
 *
 * Returns active troopers who have not attended any events
 * within the specified lookback period.
 *
 * @see GetTroopersWithoutActivityQueryHandler
 */
readonly class GetTroopersWithoutActivityQuery
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $moderator  The moderator whose managed troopers to check.
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        public readonly Trooper $moderator,
        public readonly int|string|Carbon $lookback,
    ) {}
}
