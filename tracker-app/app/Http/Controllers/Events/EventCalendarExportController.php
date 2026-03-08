<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\CalendarLinks\Link;

/**
 * Exports a single event as an iCalendar (.ics) file using spatie/calendar-links.
 */
class EventCalendarExportController extends MagicBusController
{
    /**
     * Generate an iCalendar feed for the given event.
     */
    public function __invoke(Request $request, Event $event): Response
    {
        $start = $event->event_start;
        $end = $event->event_end;

        if ($start === null)
        {
            abort(404);
        }

        if ($end === null)
        {
            $end = $start->copy()->addHours(2);
        }

        $locationParts = array_filter([
            $event->venue,
            $event->venue_address,
            $event->venue_city,
            $event->venue_state,
            $event->venue_zip,
            $event->venue_country,
        ]);

        $location = implode(', ', $locationParts);
        $description = $event->comments ? strip_tags((string) $event->comments) : '';

        $link = Link::create($event->name, $start, $end)
            ->description($description)
            ->address($location);

        $icsDataUri = $link->ics([], ['format' => 'file']);

        // When using format "file", ics() returns raw contents
        $content = $icsDataUri;

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="event-'.$event->id.'.ics"',
        ]);
    }
}
