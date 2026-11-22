<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\MapPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Upcoming events with a location, as pins on a map.
 */
class MapController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = MapPageData::call($request);

        return Inertia::render('events/Map', $data);
    }
}
