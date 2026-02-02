<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the trooper roster management form for an event.
 *
 * Provides administrators and moderators with a form to manage trooper
 * registrations and status updates for an event. Displays troopers
 * organized by shifts.
 */
class UpdateTroopersController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the trooper roster management form
     *
     * Authorizes that the user can update the event via policy check.
     * Loads event shifts with roster scope and renders the trooper
     * management view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event whose trooper roster is being managed (route model binding)
     * @return View The trooper roster management form view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $event_shifts = $event->event_shifts()->roster()->get();

        $data = compact('event', 'event_shifts');

        return view('pages.admin.events.troopers', $data);
    }
}
