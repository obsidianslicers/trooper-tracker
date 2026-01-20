<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Http\Controllers\MagicBusController;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the calendar view of upcoming events.
 *
 * This controller renders the main events listing page, showing all upcoming
 * events with their organizations, shifts, and attendance information. Only
 * events where the user's organization can attend are included.
 */
class CalendarController extends MagicBusController
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
    public function __invoke(Request $request): View
    {
        $events_query = new GetEventsForDisplayQuery();

        $events = $this->bus->send($events_query)->groupBy(fn($event) => $event->event_start->toDateString());

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery());

        $months = [];

        $start_of_calendar = now()->startOfMonth();

        for ($i = 0; $i < 12; $i++)
        {
            $month = $start_of_calendar->copy()->addMonths($i);
            $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
            $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

            $weeks = [];
            $week = [];

            for ($date = $start->copy(); $date <= $end; $date->addDay())
            {
                $week[] = $date->copy();

                if ($date->isSaturday())
                {
                    $weeks[] = ['days' => $week];
                    $week = [];
                }
            }

            $months[] = ['date' => $month, 'weeks' => $weeks,];
        }

        $data = compact('events', 'months', 'costume_organizations');

        return view('pages.events.calendar', $data);
    }
}
