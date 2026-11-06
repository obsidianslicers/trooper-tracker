<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\Queries\GetMostRecentEventsForTrooper;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of a trooper's events history page.
 *
 * Presents a trooper's recent event participation history for administrators
 * and moderators to review their activity and attendance records.
 */
class EventsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's events page
     *
     * This method authorizes the user, sets up breadcrumbs, loads the trooper's
     * recent events via GetMostRecentEventsForTrooper, and returns the view
     * showing the trooper's event participation history.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper whose events history is to be displayed
     * @return View The rendered authority page view
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('update', $trooper);

        $organization_events = GetMostRecentEventsForTrooper::call(trooper: $trooper);

        $data = compact('trooper', 'organization_events');

        return view('pages.admin.troopers.events', $data);
    }
}
