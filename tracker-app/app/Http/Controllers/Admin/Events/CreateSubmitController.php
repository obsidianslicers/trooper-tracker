<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\CreateRequest;
use App\Models\Base\EventShift;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Services\FlashMessageService;
use App\Services\GeocodingService;
use App\Services\GoogleService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Throwable;

/**
 * Processes event creation form submissions.
 *
 * Handles the creation of new events from form submissions, including events
 * created from email sources. Creates the event record along with associated
 * organization relationships and initial shift data.
 */
class CreateSubmitController extends Controller
{
    /**
     * Creates a new CreateFromEmailSubmitController instance.
     *
     * @param FlashMessageService $flash Service for displaying flash messages to users.
     */
    public function __construct(
        private readonly FlashMessageService $flash,
        private readonly GeocodingService $geocoding)
    {
    }

    /**
     * Handle the incoming request to update a event.
     *
     * Validates the request, updates the event's properties, saves it,
     * and then redirects with a success message.
     *
     * @param CreateRequest $request The validated request containing the updated data.
     * @param Event $event The event to be updated.
     * @return RedirectResponse A redirect response to the events list.
     */
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $organization = Organization::findOrFail($request->validated('organization_id'));

        $costume_club = $organization->getSourceClub();

        $service_class = $costume_club->service_class;

        $event = $service_class::parseRequestAppearance($request->validated('source'));

        $event->organization_id = $organization->id;

        $event->save();

        $event_organization = new EventOrganization();
        $event_organization->event_id = $event->id;
        $event_organization->organization_id = $costume_club->id;
        $event_organization->can_attend = true;
        $event_organization->save();

        $event_shift = new EventShift();
        $event_shift->event_id = $event->id;
        $event_shift->shift_starts_at = $event->event_start;
        $event_shift->shift_ends_at = $event->event_end;
        $event_shift->save();

        $this->flash->created($event);

        return redirect()->route('admin.events.update', compact('event'));
    }

}

