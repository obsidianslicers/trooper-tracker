<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Features\Events\Commands\UpdateEventCommand;
use App\Features\Events\Commands\UpdateEventOrganizationsCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateRequest;
use App\Jobs\ReconcileEventRosterJob;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Processes event update form submissions.
 *
 * Handles updating existing event details including venue information, contact details,
 * event dates, amenities, and organization associations. Updates both the event record
 * and its related EventOrganization pivot records for access control.
 */
class UpdateSubmitController extends MagicBusController
{
    /**
     * Updates an existing event from the validated form submission
     *
     * Processes the validated request to update the event's properties
     * and organization access permissions. Redirects back to the update
     * form with a success message.
     *
     * @param  UpdateRequest  $request  The validated event update request
     * @param  Event  $event  The event to update (route model binding)
     * @return RedirectResponse Redirect to the event's update page
     */
    public function __invoke(
        UpdateRequest $request,
        Event $event): RedirectResponse
    {
        $current_status = $event->status;
        $updated_status = EventStatus::from($request->validated('status'));

        $this->bus->send(new UpdateEventCommand($event, $request->validated()));
        $this->bus->send(new UpdateEventOrganizationsCommand($event, $request->validated('organizations') ?? []));

        dispatch(new ReconcileEventRosterJob($event, Auth::user()));

        if ($current_status != $updated_status)
        {
            if ($current_status == EventStatus::DRAFT)
            {
                if (
                    $updated_status == EventStatus::OPEN
                    || $updated_status == EventStatus::MANUAL_SELECTION
                    || $updated_status == EventStatus::SIGN_UP_LOCKED
                ) {
                    $update_shift_status = function () use ($event, $updated_status) {
                        foreach ($event->event_shifts as $shift)
                        {
                            $shift->status = $updated_status;
                            $shift->save();
                        }
                    };

                    dispatch($update_shift_status)->afterResponse();
                    dispatch(new SendEventCreatedNotificationsJob($event));
                }
            }
            elseif ($updated_status == EventStatus::CANCELLED)
            {
                dispatch(new SendEventCancelledNotificationsJob($event));
            }
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.update', compact('event'));
    }

    // private function resetLimits(Event $event)
    // {
    //     if ($event->has_organization_limits)
    //     {
    //         $organizations = Organization::all();

    //         foreach ($organizations as $organization)
    //         {
    //             EventOrganization::updateOrCreate(
    //                 [
    //                     EventOrganization::EVENT_ID => $event->id,
    //                     EventOrganization::ORGANIZATION_ID => $organization->id,
    //                 ],
    //                 [
    //                     EventOrganization::CAN_ATTEND => true,
    //                     EventOrganization::TROOPERS_ALLOWED => null,
    //                     EventOrganization::HANDLERS_ALLOWED => null,
    //                 ]);
    //         }
    //     }
    //     else
    //     {
    //         $event->organizations()->update([
    //             EventOrganization::CAN_ATTEND => false,
    //             EventOrganization::TROOPERS_ALLOWED => null,
    //             EventOrganization::HANDLERS_ALLOWED => null,
    //         ]);
    //     }
    // }

    // private function allocate(Event $event, int $capacity = 500)
    // {
    //     $event->troopers_allowed = $capacity;
    //     $event->save();

    //     // Step 1: Gather historical participation counts per organization
    //     $orgParticipation = EventTrooper::query()
    //         ->join('tt_trooper_assignments', 'tt_event_troopers.trooper_id', '=', 'tt_trooper_assignments.trooper_id')
    //         ->where('tt_trooper_assignments.is_member', true)
    //         ->select('tt_trooper_assignments.organization_id', DB::raw('COUNT(*) as total'))
    //         ->groupBy('tt_trooper_assignments.organization_id')
    //         ->pluck('total', 'tt_trooper_assignments.organization_id');

    //     // Step 2: Apply weight to hosting org
    //     $orgParticipation = $orgParticipation->map(function ($count, $orgId) use ($event)
    //     {
    //         return $orgId == $event->organization_id ? $count * 2 : $count;
    //     });

    //     if ($orgParticipation->isEmpty())
    //     {
    //         return;
    //     }

    //     // Step 2: Compute proportions
    //     $totalTroopers = $orgParticipation->sum();

    //     $distribution = $orgParticipation->map(function ($count) use ($totalTroopers)
    //     {
    //         return $count / $totalTroopers;
    //     });

    //     // Step 3: Allocate slots
    //     $allocation = $distribution->map(function ($fraction) use ($capacity)
    //     {
    //         return (int) round($fraction * $capacity);
    //     });

    //     // Step 4: Store in pivot table (update or create)
    //     foreach ($allocation as $orgId => $slots)
    //     {
    //         $can_update = $event->organizations()->where(EventOrganization::ORGANIZATION_ID, $orgId)->exists();

    //         if ($can_update)
    //         {
    //             // Update if it already exists
    //             $event->organizations()->updateExistingPivot($orgId,
    //                 [
    //                     EventOrganization::CAN_ATTEND => true,
    //                     EventOrganization::TROOPERS_ALLOWED => $slots,
    //                     EventOrganization::HANDLERS_ALLOWED => null,
    //                 ]);
    //         }
    //         else
    //         {
    //             // Otherwise create
    //             $event->organizations()->attach($orgId,
    //                 [
    //                     EventOrganization::CAN_ATTEND => true,
    //                     EventOrganization::TROOPERS_ALLOWED => $slots,
    //                     EventOrganization::HANDLERS_ALLOWED => null,
    //                 ]);
    //         }
    //     }
    // }
}
