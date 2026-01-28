<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Models\Trooper;
use Carbon\Carbon;

/**
 * @see GetEventVolunteerCountQueryHandler
 */
readonly class GetEventVolunteerCountQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $moderator The moderator whose change history to retrieve.
     * @param int|string|Carbon $lookback Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        public readonly Trooper $moderator,
        public readonly int|string|Carbon $lookback,
    ) {
    }
}