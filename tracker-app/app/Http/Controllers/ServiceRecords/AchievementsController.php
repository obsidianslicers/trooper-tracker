<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Troopers\Queries\GetTrooperAchievementsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays trooper achievement activity.
 */
class AchievementsController extends MagicBusController
{
    /**
     * Retrieves achievements for a lookback window and renders the achievements view.
     *
     * @throws \RuntimeException
     */
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', '30');

        $query = new GetTrooperAchievementsQuery($days);

        $trooper_achievements = $this->bus->send($query);

        $data = compact('days', 'trooper_achievements');

        return view('pages.service-records.achievements', $data);
    }
}
