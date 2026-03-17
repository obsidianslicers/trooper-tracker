<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the event sign-up page with all shifts and current trooper assignments.
 *
 * This controller renders the event sign-up interface where troopers can view
 * available shifts, see who is already signed up, and register for shifts.
 * It loads the event with all related data including organizations, shifts,
 * and trooper assignments.
 */
class EventDisplayController extends MagicBusController
{
    /**
     * Initialize controller by setting up breadcrumb navigation.
     */
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Events', 'events.list');
    }

    /**
     * Handle the incoming request to display the event sign-up page
     *
     * Loads the event with all shifts, trooper assignments, and organization
     * details. Each shift is enriched with its parent event reference for
     * easy access in the view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event to display for sign-up
     * @return View The rendered event sign-up page
     */
    public function __invoke(Request $request, Event $event): View
    {
        $trooper = $request->user();

        $event_query = new GetEventDisplayQuery($event, $trooper);

        $event = $this->bus->send($event_query);

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        // Header background color
        $bg = $event->at_risk ? 'bg-danger' : 'bg-primary';
        if ($event->is_locked)
        {
            $bg = 'bg-secondary';
        }

        $data = compact('event', 'can_moderate', 'bg');

        return view('pages.events.event-display', $data);
    }
}
