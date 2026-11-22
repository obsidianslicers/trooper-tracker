<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\CalendarPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The next twelve months of upcoming events as month calendars.
 */
class ShowEventCalendarController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = CalendarPageData::call($request);

        return Inertia::render('events/Calendar', $data);
    }
}
