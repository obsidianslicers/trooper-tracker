<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Mail\Events\TrooperManualSelectionApproved;
use App\Models\Event;
use App\Models\EventGuest;
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
        $guests = $request->validated('guests', []);
        $approveTrooperIds = collect($request->input('approve_trooper_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();
        $rejectTrooperIds = collect($request->input('reject_trooper_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();
        $approveGuestIds = collect($request->input('approve_guest_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();
        $rejectGuestIds = collect($request->input('reject_guest_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        $event_troopers = $event->troopers()->get();
        $event_guest_shift_ids = $event->event_shifts()->pluck('id');
        $event_guests = EventGuest::query()
            ->whereIn(EventGuest::EVENT_SHIFT_ID, $event_guest_shift_ids)
            ->get();
        $authTrooper = $request->user();
        $isManualSelectionEvent = $event->status === EventStatus::MANUAL_SELECTION;

        if ($isManualSelectionEvent)
        {
            foreach ($approveTrooperIds as $approveTrooperId)
            {
                $troopers[$approveTrooperId]['status'] = EventTrooperStatus::GOING->value;
            }
            foreach ($rejectTrooperIds as $rejectTrooperId)
            {
                $troopers[$rejectTrooperId]['status'] = EventTrooperStatus::STAND_BY->value;
            }

            foreach ($approveGuestIds as $approveGuestId)
            {
                $guests[$approveGuestId]['status'] = EventGuestStatus::GOING->value;
            }
            foreach ($rejectGuestIds as $rejectGuestId)
            {
                $guests[$rejectGuestId]['status'] = EventGuestStatus::STAND_BY->value;
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

        foreach ($guests as $id => $input)
        {
            $event_guest = $event_guests->first(fn ($eg) => $eg->id === (int) $id);

            if ($event_guest === null)
            {
                continue;
            }

            $newStatus = $input['status'] ?? null;
            if ($newStatus === null)
            {
                continue;
            }

            $event_guest->status = $newStatus;
            $event_guest->save();
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.troopers', compact('event'));
    }
}
