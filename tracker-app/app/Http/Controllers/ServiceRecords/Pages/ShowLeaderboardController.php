<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\LeaderboardPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays service record leaderboard metrics.
 */
class ShowLeaderboardController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = LeaderboardPageData::call($request);

        return Inertia::render('service-records/Leaderboard', $data);
    }
}
