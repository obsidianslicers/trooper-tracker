<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\ListPastEventsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The completed events list, with each event's totals.
 */
class ClosedController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = ListPastEventsPageData::call(actor: $request->user(), status: EventStatus::CLOSED);

        return Inertia::render('events/PastEvents', $data);
    }
}
