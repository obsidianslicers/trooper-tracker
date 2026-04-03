<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use App\Features\Organizations\Queries\GetOrganizationsForPickerQuery;
use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Http\Controllers\MagicBusController;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the calendar view of upcoming events.
 *
 * This controller renders a 12-month calendar grid view showing upcoming
 * events grouped by date. Each month displays weeks with events marked
 * on their corresponding dates.
 */
class CalendarController extends MagicBusController
{
    /**
     * Handle the incoming request to display the calendar page
     *
     * Retrieves all upcoming events grouped by date and generates a 12-month
     * calendar grid (weeks starting Sunday, ending Saturday) for displaying
     * events in a calendar layout.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered events calendar page with upcoming events
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $events_query = new GetEventsForDisplayQuery;

        $events = $this->bus->send($events_query)->groupBy(fn($event) => $event->event_start->toDateString());

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery);

        $hosting_organizations = $this->bus->send(new GetOrganizationsForPickerQuery($trooper, []));

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

            $months[] = ['date' => $month, 'weeks' => $weeks];
        }

        $data = compact('events', 'months', 'costume_organizations', 'hosting_organizations');

        return view('pages.events.calendar', $data);
    }
}
