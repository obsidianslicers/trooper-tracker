<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\AchievementsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays trooper achievement activity.
 */
class ShowAchievementsController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = AchievementsPageData::call($request);

        return Inertia::render('service-records/Achievements', $data);
    }
}
