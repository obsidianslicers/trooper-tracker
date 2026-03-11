<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays service record leaderboard metrics.
 */
class LeaderboardController extends MagicBusController
{
    /**
     * Retrieves leaderboard metrics for a lookback window and renders the leaderboard view.
     *
     * @throws \RuntimeException
     */
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', '30');

        $leaderboard = $this->bus->send(new GetLeaderboardMetricsQuery($days));

        $data = compact('leaderboard', 'days');

        return view('pages.service-records.leaderboard', $data);
    }
}
