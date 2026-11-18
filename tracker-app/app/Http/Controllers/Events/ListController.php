<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Facades\TroopTrackerFacade as TroopTracker;
use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\ListEventsPageData;
use App\Messages\Support\Queries\GetSupportGoal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The upcoming events list, where troopers land after signing in.
 */
class ListController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = ListEventsPageData::call($request);

        $data['support'] = TroopTracker::isSupportGoalEnabled()
            ? Inertia::defer(fn (): array => GetSupportGoal::call())
            : null;

        return Inertia::render('events/List', $data);
    }
}
