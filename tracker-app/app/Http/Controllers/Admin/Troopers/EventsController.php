<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Queries\GetMostRecentEventsForTrooperQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of a trooper's events history page.
 *
 * Presents a trooper's recent event participation history for administrators
 * and moderators to review their activity and attendance records.
 */
class EventsController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's events page.
     *
     * This method authorizes the user, sets up breadcrumbs, loads the trooper's
     * recent events via GetMostRecentEventsForTrooperQuery, and returns the view
     * showing the trooper's event participation history.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Trooper  $trooper  The trooper whose events history is to be displayed.
     * @return View The rendered authority page view.
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('update', $trooper);

        $recent_events_query = new GetMostRecentEventsForTrooperQuery($trooper);

        $organization_events = $this->bus->send($recent_events_query);

        $data = compact('trooper', 'organization_events');

        return view('pages.admin.troopers.events', $data);
    }
}
