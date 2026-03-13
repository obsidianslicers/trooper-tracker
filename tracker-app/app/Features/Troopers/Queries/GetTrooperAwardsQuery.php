<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Features\Concerns\HasLookback;
use Carbon\Carbon;

/**
 * Defines criteria for retrieving awarded trooper records.
 *
 * Supports lookback filtering by days (int), date string, or Carbon instance.
 *
 * @see GetTrooperAwardsQueryHandler
 */
readonly class GetTrooperAwardsQuery
{
    use HasLookback;

    /**
     * Initializes the awards query.
     *
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        public readonly int|string|Carbon $lookback,
    ) {}
}
