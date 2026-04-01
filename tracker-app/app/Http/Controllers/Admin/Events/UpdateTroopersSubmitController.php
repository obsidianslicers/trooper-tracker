<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Mail\Events\TrooperManualSelectionApproved;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

        $troopers = $request->validated('troopers', []);
        $approveTrooperIds = collect($request->input('approve_trooper_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        $event_troopers = $event->troopers()->get();
        $authTrooper = $request->user();
        $isManualSelectionEvent = $event->status === EventStatus::MANUAL_SELECTION;

        if ($isManualSelectionEvent)
        {
            foreach ($approveTrooperIds as $approveTrooperId)
            {
                $troopers[$approveTrooperId]['status'] = EventTrooperStatus::GOING->value;
            }
        }

        foreach ($troopers as $id => $input)
        {
            $event_trooper = $event_troopers->filter(fn ($et) => $et->id === (int) $id)->first();

            if ($event_trooper === null)
            {
                continue;
            }

            $newStatus = $input['status'] ?? null;
            if ($newStatus === null)
            {
                continue;
            }

            $oldStatus = $event_trooper->status;

            $event_trooper->status = $newStatus;

            $event_trooper->save();

            $wasManualApproval = $isManualSelectionEvent
                && $oldStatus === EventTrooperStatus::STAND_BY
                && $event_trooper->status === EventTrooperStatus::GOING;

            if ($wasManualApproval)
            {
                Mail::to($event_trooper->trooper->email)->queue(new TrooperManualSelectionApproved($event_trooper, $authTrooper));
            }
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.troopers', compact('event'));
    }
}
