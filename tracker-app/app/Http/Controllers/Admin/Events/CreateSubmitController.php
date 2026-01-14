<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\CreateRequest;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Base\EventShift;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Services\Events\UpdateEventCommand;
use App\Services\Events\UpdateEventOrganizationsCommand;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Processes event creation form submissions.
 *
 * Handles the creation of new events from form submissions. Creates the event record
 * along with an initial EventOrganization record for the source club, a default
 * EventShift matching the event times, and any additional organization associations
 * from the form. Dispatches notification jobs for events created in OPEN or
 * SIGN_UP_LOCKED status.
 */
class CreateSubmitController extends Controller
{
    /**
     * Creates a new CreateSubmitController instance.
     *
     * @param FlashMessageService $flash Service for displaying flash messages to users.
     */
    public function __construct(
        private readonly FlashMessageService $flash)
    {
    }

    /**
     * Creates a new event from validated form submission.
     *
     * Validates the request, creates a new event with the provided data,
     * creates an EventOrganization record for the source club, creates an
     * initial EventShift matching the event times, updates organization
     * associations, and dispatches notifications if the event is published.
     * Redirects to the event's update page with a success message.
     *
     * @param CreateRequest $request The validated request containing the event data.
     * @param UpdateEventCommand $udpate_event Command to update event properties.
     * @param UpdateEventOrganizationsCommand $update_event_organizations Command to update organization associations.
     * @return RedirectResponse Redirect to the new event's update page.
     */
    public function __invoke(
        CreateRequest $request,
        UpdateEventCommand $udpate_event,
        UpdateEventOrganizationsCommand $update_event_organizations): RedirectResponse
    {
        $organization = Organization::findOrFail($request->validated('organization_id'));

        $costume_club = $organization->getSourceClub();

        $event = new Event();

        $event->organization_id = $organization->id;

        $udpate_event($event, $request->validated());

        if ($event->status == EventStatus::OPEN || $event->status == EventStatus::SIGN_UP_LOCKED)
        {
            dispatch(new SendEventCreatedNotificationsJob($event));
        }

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

        $update_event_organizations($event, $request->validated('organizations') ?? []);

        $this->flash->created($event);

        return redirect()->route('admin.events.update', compact('event'));
    }
}

