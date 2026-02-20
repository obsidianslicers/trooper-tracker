<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
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
     * Authorizes administrative permission to update the event via policy.
     * Retrieves event shifts with enriched trooper and costume data via
     * GetTroopersForEventAdminQuery and renders the roster management view.
     *
     * @param  Request  $request  The incoming HTTP request (unused)
     * @param  Event  $event  The event whose trooper roster is being managed (route model binding)
     * @return View The trooper roster management form view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $query = new GetTroopersForEventAdminQuery($event);

        $event_shifts = $this->bus->send($query);

        $data = compact('event', 'event_shifts');

        return view('pages.admin.events.troopers', $data);
    }
}
