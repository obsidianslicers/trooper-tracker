<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Processes trooper status update form submissions.
 *
 * Handles updating the status of event trooper registrations (approved, standby, etc.).
 * Iterates through submitted trooper IDs and updates their EventTrooper status.
 */
class UpdateTroopersSubmitController extends MagicBusController
{
    /**
     * Updates event trooper statuses from the validated form submission
     *
     * Processes the validated request to update status values for troopers
     * registered to the event. Only updates troopers that exist in the event.
     * Redirects back to the trooper management page with a success message.
     *
     * @param  UpdateTroopersRequest  $request  The validated trooper status update request
     * @param  Event  $event  The event whose troopers are being updated (route model binding)
     * @return RedirectResponse Redirect to the event's trooper management page
     */
    public function __invoke(UpdateTroopersRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $troopers = $request->validated('troopers');

        $event_troopers = $event->troopers()->get();

        foreach ($troopers as $id => $input)
        {
            $event_trooper = $event_troopers->filter(fn ($et) => $et->id === (int) $id)->first();

            if ($event_trooper === null)
            {
                continue;
            }

            $event_trooper->status = $input['status'];

            $event_trooper->save();
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.troopers', compact('event'));
    }
}
