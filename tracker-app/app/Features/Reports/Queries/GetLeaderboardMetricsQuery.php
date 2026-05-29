<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Features\Concerns\HasLookback;
use App\Models\Organization;
use Carbon\Carbon;

/**
 * Query to retrieve dashboard metrics for a lookback period.
 *
 * Provides the time window used by the dashboard metrics handler.
 *
 * @see GetLeaderboardMetricsQueryHandler
 */
readonly class GetLeaderboardMetricsQuery
{
    use HasLookback {
        parseLookback as private parseRequiredLookback;
    }

    /**
     * Create a new query instance.
     *
     * @param  int|string|Carbon|null  $lookback  Days to look back, date string,
     *                                            Carbon date, or null for all time.
     */
    public function __construct(
        public readonly int|string|Carbon|null $lookback = null,
        public readonly ?Organization $organization = null,
        public readonly int $limit = 30,
    ) {}

    public function parseLookback(): ?Carbon
    {
        if ($this->lookback === null)
        {
            return null;
        }

        return $this->parseRequiredLookback();
    }
}
