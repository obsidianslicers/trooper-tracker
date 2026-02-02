<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the shift management form for an event.
 *
 * Provides administrators and moderators with a form to manage event shifts,
 * including creating new shifts and updating existing shift times.
 */
class UpdateShiftsController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the shift management form
     *
     * Authorizes that the user can update the event via policy check.
     * Loads existing shifts ordered by start time and renders the shift
     * management view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event whose shifts are being managed (route model binding)
     * @return View The shift management form view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $shifts = $event->event_shifts()->orderBy(EventShift::SHIFT_STARTS_AT)->get();

        $data = compact('event', 'shifts');

        return view('pages.admin.events.shifts', $data);
    }
}
