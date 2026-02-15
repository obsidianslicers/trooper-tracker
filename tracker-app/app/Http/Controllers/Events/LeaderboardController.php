<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the leaderboard page.
 *
 * This controller renders the leaderboard for events.
 */
class LeaderboardController extends MagicBusController
{
    /**
     * Handle the incoming request to display the leaderboard page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered leaderboard view
     */
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', '30');

        $leaderboard = $this->bus->send(new GetLeaderboardMetricsQuery($days));

        $data = compact('leaderboard', 'days');

        return view('pages.events.leaderboard', $data);
    }
}
