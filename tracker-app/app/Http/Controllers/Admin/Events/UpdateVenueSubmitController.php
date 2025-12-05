<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\UpdateVenueRequest;
use App\Models\Event;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class UpdateVenueSubmitController extends Controller
{
    /**
     * UpdateSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update a event.
     *
     * Validates the request, updates the event's properties, saves it,
     * and then redirects with a success message.
     *
     * @param UpdateVenueRequest $request The validated request containing the updated data.
     * @param Event $event The event to be updated.
     * @return RedirectResponse A redirect response to the events list.
     */
    public function __invoke(UpdateVenueRequest $request, Event $event): RedirectResponse
    {
        $event_venue = $event->event_venue;

        // Coordinates
        $event_venue->latitude = $request->validated('latitude');
        $event_venue->longitude = $request->validated('longitude');

        // Contact info
        $event_venue->contact_name = $request->validated('contact_name');
        $event_venue->contact_phone = $request->validated('contact_phone');
        $event_venue->contact_email = $request->validated('contact_email');

        // Event details
        $event_venue->event_name = $request->validated('event_name');
        $event_venue->venue = $request->validated('venue');
        $event_venue->venue_address = $request->validated('venue_address');
        $event_venue->venue_city = $request->validated('venue_city');
        $event_venue->venue_state = $request->validated('venue_state');
        $event_venue->venue_zip = $request->validated('venue_zip');
        $event_venue->venue_country = $request->validated('venue_country');
        $event_venue->event_start = $request->validated('event_start');
        $event_venue->event_end = $request->validated('event_end');
        $event_venue->event_website = $request->validated('event_website');

        // Request specifics
        $event_venue->expected_attendees = $request->validated('expected_attendees');
        $event_venue->requested_characters = $request->validated('requested_characters');
        $event_venue->requested_character_types = $request->validated('requested_character_types');

        // Venue amenities / permissions
        $event_venue->secure_staging_area = $request->validated('secure_staging_area');
        $event_venue->allow_blasters = $request->validated('allow_blasters');
        $event_venue->allow_props = $request->validated('allow_props');
        $event_venue->parking_available = $request->validated('parking_available');
        $event_venue->accessible = $request->validated('accessible');
        $event_venue->amenities = $request->validated('amenities');

        // Misc
        $event_venue->comments = $request->validated('comments');
        $event_venue->referred_by = $request->validated('referred_by');

        $event_venue->save();

        $this->flash->updated($event);

        return redirect()->route('admin.events.update', ['event' => $event]);
    }
}

