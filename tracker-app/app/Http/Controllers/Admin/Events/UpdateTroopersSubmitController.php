<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Models\Event;
use App\Models\EventGuest;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use Illuminate\Http\RedirectResponse;

/**
 * Processes trooper status update form submissions.
 *
 * Handles updating the status of event trooper registrations (approved, standby, etc.).
 */
class UpdateTroopersSubmitController extends MagicBusController
{
    public function __invoke(UpdateTroopersRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $troopers = $request->validated('troopers', []);
        $guests = $request->validated('guests', []);

        $event_troopers = $event->troopers()->with('trooper.organizations')->get();
        $event_guest_shift_ids = $event->event_shifts()->pluck('id');
        $event_guests = EventGuest::query()
            ->whereIn(EventGuest::EVENT_SHIFT_ID, $event_guest_shift_ids)
            ->get();
        $authTrooper = $request->user();
        $isManualSelectionEvent = $event->status === EventStatus::MANUAL_SELECTION;

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

            $newOrgId = isset($input['organization_id']) && $input['organization_id'] !== ''
                ? (int) $input['organization_id']
                : null;

            $trooperOrgIds = $event_trooper->trooper->organizations->pluck('id')->toArray();
            $event_trooper->organization_id = ($newOrgId !== null && in_array($newOrgId, $trooperOrgIds, true))
                ? $newOrgId
                : null;

            $event_trooper->save();

            $wasManualApproval = $isManualSelectionEvent
                && $oldStatus === EventTrooperStatus::STAND_BY
                && $event_trooper->status === EventTrooperStatus::GOING;
            $wasMovedToStandBy = $isManualSelectionEvent
                && $oldStatus === EventTrooperStatus::GOING
                && $event_trooper->status === EventTrooperStatus::STAND_BY;

            if ($wasManualApproval)
            {
                $event_trooper->trooper->notify(new ManualSelectionApprovedNotification($event_trooper, $authTrooper));
            }

            if ($wasMovedToStandBy)
            {
                $event_trooper->trooper->notify(new ManualSelectionStandByNotification($event_trooper, $authTrooper));
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
