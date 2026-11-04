<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Http\Controllers\MagicBusController;
use App\Messages\Troopers\Queries\GetTrooperAchievements;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Displays trooper achievement activity.
 */
class AchievementsController extends MagicBusController
{
    /**
     * Retrieves achievements for a lookback window and renders the achievements view.
     *
     * @throws RuntimeException
     */
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', '30');

        $trooper_achievements = GetTrooperAchievements::call(lookback: $days);

        $data = compact('days', 'trooper_achievements');

        return view('pages.service-records.achievements', $data);
    }
}
