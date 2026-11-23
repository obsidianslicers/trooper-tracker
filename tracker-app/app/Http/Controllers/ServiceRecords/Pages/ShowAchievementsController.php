<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\AchievementsPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays trooper achievement activity.
 */
class ShowAchievementsController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = AchievementsPageData::call($request);

        return view('pages.service-records.achievements', $data);
    }
}
