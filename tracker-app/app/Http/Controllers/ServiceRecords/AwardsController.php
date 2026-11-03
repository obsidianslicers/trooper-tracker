<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\Queries\GetTrooperAwards;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Displays awarded trooper activity.
 */
class AwardsController extends PageController
{
    /**
     * Retrieves award recipients for a lookback window and renders the awards view.
     *
     * @throws RuntimeException
     */
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', '30');

        $award_troopers = GetTrooperAwards::call(lookback: $days);

        $data = compact('award_troopers', 'days');

        return view('pages.service-records.awards', $data);
    }
}
