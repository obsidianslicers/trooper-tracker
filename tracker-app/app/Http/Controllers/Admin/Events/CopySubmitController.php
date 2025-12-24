<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\CopyRequest;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

/**
 * Processes event update form submissions.
 *
 * Handles updating existing event details including venue information, contact details,
 * event dates, amenities, and organization associations. Copies both the event record
 * and its related EventOrganization pivot records for access control.
 */
class CopySubmitController extends Controller
{
    /**
     * Creates a new CopySubmitController instance.
     *
     * @param FlashMessageService $flash Service for displaying flash messages to users.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Copies an existing event from the validated form submission.
     *
     * Processes the validated request to update the event's properties
     * and organization access permissions. Redirects back to the update
     * form with a success message.
     *
     * @param CopyRequest $request The validated event update request.
     * @param Event $event The event to update (route model binding).
     * @return RedirectResponse Redirect to the event's update page.
     */
    public function __invoke(CopyRequest $request, Event $event): RedirectResponse
    {
        $trooper = $request->user();

        $event_copy = $this->copyEvent($request, $trooper, $event);

        $this->flash->updated($event_copy);

        return redirect()->route('admin.events.update', ['event' => $event_copy]);
    }

    private function copyEvent(CopyRequest $request, Trooper $trooper, Event $event): Event
    {
        $old_start = $event->event_start;
        $new_start = Carbon::parse($request->validated(Event::EVENT_START));
        $diff = $old_start->diffAsCarbonInterval($new_start);

        $event_copy = $event->replicate();
        $event_copy->name = 'Copy of ' . $request->validated(Event::NAME);
        $event_copy->event_start = $event->event_start->add($diff);
        $event_copy->event_end = $event->event_end->add($diff);
        $event_copy->status = EventStatus::DRAFT;
        $event_copy->push();

        foreach ($event->event_shifts as $shift)
        {
            $shift_copy = $shift->replicate();
            $shift_copy->event_id = $event_copy->id;
            $shift_copy->shift_starts_at = $shift->shift_starts_at->add($diff);
            $shift_copy->shift_ends_at = $shift->shift_ends_at->add($diff);
            $shift_copy->status = EventStatus::OPEN;
            $shift_copy->save();
        }

        foreach ($event->event_organizations as $organization)
        {
            $organization_copy = $organization->replicate();
            $organization_copy->event_id = $event_copy->id;
            $organization_copy->save();
        }

        return $event_copy;
    }

}

