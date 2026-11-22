<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\EventDisplayPageData;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The event page: its details, each shift's roster and Sign Up, and the photos.
 */
class EventDisplayController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('events.Events'), 'events.list');
    }

    public function __invoke(Request $request, Event $event): InertiaResponse
    {
        $data = EventDisplayPageData::call($request);

        return Inertia::render('events/Display', $data);
    }
}
