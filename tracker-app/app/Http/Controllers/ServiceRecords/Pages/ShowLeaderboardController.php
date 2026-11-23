<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\LeaderboardPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays service record leaderboard metrics.
 */
class ShowLeaderboardController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = LeaderboardPageData::call($request);

        return view('pages.service-records.leaderboard', $data);
    }
}
