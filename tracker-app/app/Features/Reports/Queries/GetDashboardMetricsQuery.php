<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Features\Reports\Concerns\HasLookback;
use Carbon\Carbon;

/**
 * Query to retrieve dashboard metrics for a lookback period.
 *
 * Provides the time window used by the dashboard metrics handler.
 *
 * @see GetDashboardMetricsQueryHandler
 */
readonly class GetDashboardMetricsQuery
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(public readonly int|string|Carbon $lookback) {}
}
