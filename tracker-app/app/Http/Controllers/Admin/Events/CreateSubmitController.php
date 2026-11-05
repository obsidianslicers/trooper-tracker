<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Events\CreateRequest;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Messages\Events\Commands\UpdateEvent;
use App\Messages\Events\Commands\UpdateEventOrganizations;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

/**
 * Processes event creation form submissions.
 *
 * Handles the creation of new events from form submissions. Creates the event record
 * along with a default EventShift matching the event times, and any organization
 * associations from the form. Dispatches notification jobs for events created in
 * OPEN or SIGN_UP_LOCKED status.
 */
class CreateSubmitController extends PageController
{
    /**
     * Creates a new event from validated form submission
     *
     * Validates the request, creates a new event with the provided data,
     * creates an initial EventShift matching the event times, updates
     * organization associations, and dispatches notifications if the event
     * is published. Redirects to the event's update page with a success message.
     *
     * @param  CreateRequest  $request  The validated request containing the event data
     * @return RedirectResponse Redirect to the new event's update page
     */
    public function __invoke(
        CreateRequest $request): RedirectResponse
    {
        $organization = Organization::findOrFail($request->validated('organization_id'));

        $event = new Event;

        $event->organization_id = $organization->id;

        UpdateEvent::call(event: $event, data: $request->validated());

        if (
            $event->status == EventStatus::OPEN
            || $event->status == EventStatus::MANUAL_SELECTION
            || $event->status == EventStatus::SIGN_UP_LOCKED
        ) {
            dispatch(new SendEventCreatedNotificationsJob($event));
        }

        $event_shift = new EventShift;
        $event_shift->event_id = $event->id;
        $event_shift->shift_starts_at = $event->event_start;
        $event_shift->shift_ends_at = $event->event_end;
        $event_shift->status = $event->status;
        $event_shift->save();

        UpdateEventOrganizations::call(
            event: $event,
            data: $request->validated('organizations') ?? [],
        );

        FlashType::created($event);

        return redirect()->route('admin.events.update', compact('event'));
    }
}
