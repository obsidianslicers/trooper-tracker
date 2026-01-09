<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\UpdateRequest;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Processes event update form submissions.
 *
 * Handles updating existing event details including venue information, contact details,
 * event dates, amenities, and organization associations. Updates both the event record
 * and its related EventOrganization pivot records for access control.
 */
class UpdateSubmitController extends Controller
{
    /**
     * Creates a new UpdateSubmitController instance.
     *
     * @param FlashMessageService $flash Service for displaying flash messages to users.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Updates an existing event from the validated form submission.
     *
     * Processes the validated request to update the event's properties
     * and organization access permissions. Redirects back to the update
     * form with a success message.
     *
     * @param UpdateRequest $request The validated event update request.
     * @param Event $event The event to update (route model binding).
     * @return RedirectResponse Redirect to the event's update page.
     */
    public function __invoke(UpdateRequest $request, Event $event): RedirectResponse
    {
        $this->updateEvent($request, $event);
        $this->updateOrganizations($request, $event);

        $this->flash->updated($event);

        return redirect()->route('admin.events.update', compact('event'));
    }

    private function updateEvent(UpdateRequest $request, Event $event): void
    {
        $current_status = $event->status;
        $updated_status = EventStatus::from($request->validated('status'));

        $event->name = $request->validated('name');
        $event->status = $request->validated('status');
        $event->troopers_allowed = $request->validated('troopers_allowed');
        $event->handlers_allowed = $request->validated('handlers_allowed');
        $event->friends_allowed = $request->validated('friends_allowed');
        $event->tentative_signups_allowed = $request->validated('tentative_signups_allowed');

        // Coordinates
        $event->latitude = $request->validated('latitude');
        $event->longitude = $request->validated('longitude');

        // Contact info
        $event->contact_name = $request->validated('contact_name');
        $event->contact_phone = $request->validated('contact_phone');
        $event->contact_email = $request->validated('contact_email');

        // Event details
        $event->venue = $request->validated('venue');
        $event->venue_address = $request->validated('venue_address');
        $event->venue_city = $request->validated('venue_city');
        $event->venue_state = $request->validated('venue_state');
        $event->venue_zip = $request->validated('venue_zip');
        $event->venue_country = $request->validated('venue_country');
        $event->event_start = $request->validated('event_start');
        $event->event_end = $request->validated('event_end');
        $event->event_website = $request->validated('event_website');

        // Request specifics
        $event->expected_attendees = $request->validated('expected_attendees');
        $event->requested_number_characters = $request->validated('requested_number_characters');
        $event->requested_character_types = $request->validated('requested_character_types');

        // Venue amenities / permissions
        $event->secure_staging_area = $request->validated('secure_staging_area');
        $event->allow_blasters = $request->validated('allow_blasters');
        $event->allow_props = $request->validated('allow_props');
        $event->parking_available = $request->validated('parking_available');
        $event->accessible = $request->validated('accessible');
        $event->amenities = $request->validated('amenities');

        // Misc
        $event->comments = $request->validated('comments');
        $event->referred_by = $request->validated('referred_by');

        $event->save();

        if ($current_status != $updated_status)
        {
            if ($current_status == EventStatus::DRAFT)
            {
                if ($updated_status == EventStatus::OPEN || $updated_status == EventStatus::SIGN_UP_LOCKED)
                {
                    dispatch(new SendEventCreatedNotificationsJob($event));
                }
            }
            elseif ($updated_status == EventStatus::CANCELLED)
            {
                dispatch(new SendEventCancelledNotificationsJob($event));
            }
        }
    }

    private function updateOrganizations(UpdateRequest $request, Event $event): void
    {
        $input = $request->validated('organizations') ?? [];

        $pivot_data = $event->organizations->pluck('id')
            ->mapWithKeys(fn($id) => [$id => [
                EventOrganization::CAN_ATTEND => false,
                EventOrganization::TROOPERS_ALLOWED => null,
                EventOrganization::HANDLERS_ALLOWED => null,
            ]])
            ->toArray();

        //  merge arrays - left wins    
        $updates = $input + $pivot_data;

        $event->organizations()->syncWithoutDetaching($updates);
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

