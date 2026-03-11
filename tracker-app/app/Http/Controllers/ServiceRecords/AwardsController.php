<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Troopers\Queries\GetTrooperAwardsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays awarded trooper activity.
 */
class AwardsController extends MagicBusController
{
    /**
     * Retrieves award recipients for a lookback window and renders the awards view.
     *
     * @throws \RuntimeException
     */
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', '30');

        $query = new GetTrooperAwardsQuery($days);

        $award_troopers = $this->bus->send($query);

        $data = compact('award_troopers', 'days');

        return view('pages.service-records.awards', $data);
    }
}
