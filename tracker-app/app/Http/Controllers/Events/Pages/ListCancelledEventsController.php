<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Enums\EventStatus;
use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\ListPastEventsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The cancelled events list, with each event's totals.
 */
class ListCancelledEventsController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = ListPastEventsPageData::call(actor: $request->user(), status: EventStatus::CANCELLED);

        return Inertia::render('events/PastEvents', $data);
    }
}
