<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Features\Concerns\HasLookback;
use Carbon\Carbon;

/**
 * Defines criteria for retrieving trooper achievements.
 *
 * Supports lookback filtering by days (int), date string, or Carbon instance.
 *
 * @see GetTrooperAchievementsQueryHandler
 */
readonly class GetTrooperAchievementsQuery
{
    use HasLookback;

    /**
     * Initializes the achievements query.
     *
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        public readonly int|string|Carbon $lookback,
    ) {}
}
