<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the list of upcoming events available for sign-up.
 *
 * This controller renders the main events listing page, showing all upcoming
 * events with their organizations, shifts, and attendance information. Only
 * events where the user's organization can attend are included.
 */
class ListController extends Controller
{
    /**
     * Handle the incoming request to display the events list page.
     *
     * Retrieves all upcoming events with their associated organizations
     * (filtered to only those that can attend), shifts, and organizer details.
     * Events are ordered by date and filtered to only show future events.
     *
     * @param Request $request The incoming request
     * @return View The rendered events list page with upcoming events
     */
    public function __invoke(Request $request, EventFilter $filter): View
    {
        $with = ['organization', 'organizations' => function ($query)
        {
            $query->wherePivot(EventOrganization::CAN_ATTEND, true);
        }];

        $query = Event::with($with)
            ->withShifts()
            ->upcoming();

        $search_term = $request->query('search_term');

        $hosting_organization = null;

        if ($filter->hasFilter())
        {
            $query = $filter->apply($query);

            $hosting_organization_id = $filter->organization_id;

            if ($hosting_organization_id)
            {
                $hosting_organization = Organization::find($filter->organization_id);
            }
        }

        $events = $query->get();

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery());

        $data = compact('events', 'filter', 'costume_organizations', 'hosting_organization');

        return view('pages.events.list', $data);
    }
}
