<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\CopyRequest;
use App\Models\Event;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

/**
 * Processes event copy form submissions.
 *
 * Handles copying an existing event including all shifts and organization associations.
 * Creates a new event record with adjusted dates based on the time difference between
 * the original and new start dates. All shifts are also copied with adjusted times.
 * The copied event is created in DRAFT status regardless of the source event status.
 */
class CopySubmitController extends MagicBusController
{
    /**
     * Creates a copy of an existing event with adjusted dates
     *
     * Processes the validated request to create a new event based on the source event.
     * Calculates the time difference between old and new start dates, then applies
     * this difference to all event and shift times. Copies all event shifts and
     * organization associations to the new event. Sets the new event to DRAFT status.
     *
     * @param  CopyRequest  $request  The validated event copy request
     * @param  Event  $event  The source event to copy (route model binding)
     * @return RedirectResponse Redirect to the copied event's update page
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
        $event_copy->name = $request->validated(Event::NAME);
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
