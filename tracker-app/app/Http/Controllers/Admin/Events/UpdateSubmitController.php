<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\UpdateRequest;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class UpdateSubmitController extends Controller
{
    /**
     * UpdateSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update a event.
     *
     * Validates the request, updates the event's properties, saves it,
     * and then redirects with a success message.
     *
     * @param UpdateRequest $request The validated request containing the updated data.
     * @param Event $event The event to be updated.
     * @return RedirectResponse A redirect response to the events list.
     */
    public function __invoke(UpdateRequest $request, Event $event): RedirectResponse
    {
        $reset_limits = $event->limit_organizations != $request->validated('limit_organizations');

        $event->name = $request->validated('name');
        $event->starts_at = $request->validated('starts_at');
        $event->ends_at = $request->validated('ends_at');
        $event->status = $request->validated('status');
        $event->limit_organizations = $request->validated('limit_organizations');
        $event->troopers_allowed = $request->validated('troopers_allowed');
        $event->handlers_allowed = $request->validated('handlers_allowed');

        $event->save();

        if ($reset_limits)
        {
            $this->resetLimits($event);
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.update', ['event' => $event]);
    }

    private function resetLimits(Event $event)
    {
        if ($event->limit_organizations)
        {
            $organizations = Organization::all();

            foreach ($organizations as $organization)
            {
                EventOrganization::updateOrCreate(
                    [
                        EventOrganization::EVENT_ID => $event->id,
                        EventOrganization::ORGANIZATION_ID => $organization->id,
                    ],
                    [
                        EventOrganization::CAN_ATTEND => true,
                        EventOrganization::TROOPERS_ALLOWED => null,
                        EventOrganization::HANDLERS_ALLOWED => null,
                    ]);
            }
            $event->organizations()->update([
            ]);
        }
        else
        {
            $event->organizations()->update([
                EventOrganization::CAN_ATTEND => false,
                EventOrganization::TROOPERS_ALLOWED => null,
                EventOrganization::HANDLERS_ALLOWED => null,
            ]);
        }
    }

    // private function allocate(Event $event, int $capacity = 500)
    // {
    //     $event->troopers_allowed = $capacity;
    //     $event->save();

    //     // Step 1: Gather historical participation counts per organization
    //     $orgParticipation = EventTrooper::query()
    //         ->join('tt_trooper_assignments', 'tt_event_troopers.trooper_id', '=', 'tt_trooper_assignments.trooper_id')
    //         ->where('tt_trooper_assignments.member', true)
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

