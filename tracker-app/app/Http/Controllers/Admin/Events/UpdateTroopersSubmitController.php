<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\TrooperAssignment;
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

        $allowed_org_ids = $authTrooper->is_administrator
            ? null
            : $authTrooper->trooper_assignments()
                ->where(TrooperAssignment::IS_MODERATOR, true)
                ->pluck(TrooperAssignment::ORGANIZATION_ID)
                ->toArray();

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

            $submittedCostumeId = isset($input['costume_id']) && $input['costume_id'] !== '' ? (int) $input['costume_id'] : null;
            $costume = $submittedCostumeId !== null ? Costume::find($submittedCostumeId) : null;

            if ($costume !== null)
            {
                $event_trooper->costume_id = $costume->id;
                $event_trooper->is_handler = $costume->countsAsHandler();
                $submittedOrgIds = array_map('intval', $input['organization_ids'] ?? []);
                $event_trooper->costume_organization_ids = $submittedOrgIds;
                // EventTrooperObserver::saving() validates submitted IDs against eligible orgs
            }
            else
            {
                $event_trooper->costume_id = null;
                $submittedOrgIds = array_map('intval', $input['organization_ids'] ?? []);
                $trooperOrgIds = $event_trooper->trooper->organizations->pluck('id')->toArray();

                $validOrgIds = array_values(array_filter(
                    $submittedOrgIds,
                    fn ($id) => in_array($id, $trooperOrgIds, true)
                    && ($allowed_org_ids === null || in_array($id, $allowed_org_ids, true))
                ));

                $event_trooper->costume_organization_ids = !empty($validOrgIds) ? $validOrgIds : null;
            }

            $event_trooper->organization_id = null;

            $event_trooper->save();

            $wasManualApproval = $isManualSelectionEvent
                && $oldStatus === EventTrooperStatus::STAND_BY
                && $event_trooper->intendsToGo();
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
