<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the map view of upcoming events.
 *
 * This controller renders a 12-month calendar grid view showing upcoming
 * events grouped by date. Each month displays weeks with events marked
 * on their corresponding dates.
 */
class MapController extends MagicBusController
{
    /**
     * Handle the incoming request to display the map page.
     *
     * Retrieves all upcoming events grouped by date and generates a 12-month
     * calendar grid (weeks starting Sunday, ending Saturday) for displaying
     * events in a calendar layout.
     *
     * @param  Request  $request  The incoming request
     * @return View The rendered events calendar page with upcoming events
     */
    public function __invoke(Request $request): View
    {
        // $events_query = new GetEventsForDisplayQuery();

        // $events = $this->bus->send($events_query)->groupBy(fn($event) => $event->event_start->toDateString());

        $columns = [
            Event::ID,
            Event::NAME,
            Event::EVENT_START,
            Event::EVENT_END,
            Event::LATITUDE,
            Event::LONGITUDE,
        ];

        $events = Event::select($columns)
            ->upcoming()
            ->whereNotNull(Event::LATITUDE)
            ->whereNotNull(Event::LONGITUDE)
            ->get()
            ->each(function (Event $event) {
                $event->lng = $event->longitude;
                $event->lat = $event->latitude;
                $event->url = route('events.display', ['event' => $event->id]);
                $event->date_range = $event->time_display;
            });

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery);

        $data = compact('events', 'costume_organizations');

        return view('pages.events.map', $data);
    }
}
