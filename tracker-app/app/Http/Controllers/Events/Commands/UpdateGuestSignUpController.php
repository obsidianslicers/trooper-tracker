<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Http\Requests\Events\Commands\UpdateGuestSignUpRequest;
use App\Messages\Events\Commands\UpdateEventGuest;
use App\Models\EventGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Changes a guest from the event page: cancelling them, signing them up again, or
 * approving and rejecting them on manual-selection events.
 */
class UpdateGuestSignUpController extends PageController
{
    use AnswersWithEventPage;

    /**
     * Validates the incoming payload and updates either the guest status or
     * guest name based on the provided input key.
     */
    public function __invoke(UpdateGuestSignUpRequest $request, EventGuest $event_guest): RedirectResponse|Response
    {
        $request->validateInputs();

        $authTrooper = Auth::user();

        if ($request->has('status'))
        {
            $event_shift = $event_guest->event_shift;
            $event = $event_shift->event;
            $requestedStatus = EventGuestStatus::from($request->validated('status'));
            $canModerateEvent = $authTrooper->can('update', $event);
            $isManualSelectionEvent = $event->status === EventStatus::MANUAL_SELECTION;
            $isManualApproval = $isManualSelectionEvent
                && $canModerateEvent
                && $event_guest->status === EventGuestStatus::STAND_BY
                && $requestedStatus === EventGuestStatus::GOING;
            $isManualRejection = $isManualSelectionEvent
                && $canModerateEvent
                && $event_guest->status === EventGuestStatus::GOING
                && $requestedStatus === EventGuestStatus::STAND_BY;

            if ($isManualSelectionEvent && $requestedStatus === EventGuestStatus::GOING && !$canModerateEvent)
            {
                return response('Forbidden', 403);
            }

            if (!$event_guest->canUpdateStatus($authTrooper) && !$isManualApproval && !$isManualRejection)
            {
                return response('Forbidden', 403);
            }

            $valid_data = ['status' => $requestedStatus];

            UpdateEventGuest::call(event_guest: $event_guest, valid_data: $valid_data);

            return $this->backToEvent($event, $requestedStatus === EventGuestStatus::CANCELLED
                ? __('events.Sign-up cancelled.')
                : __('events.Saved.'));
        }
        elseif ($request->has('name'))
        {
            //  costume organization ids handled via observer, so we
            //  only need to update the name on the event guest record here
            $valid_data = [
                EventGuest::NAME => $request->validated('name'),
            ];

            UpdateEventGuest::call(event_guest: $event_guest, valid_data: $valid_data);
        }

        return $this->backToEvent($event_guest->event_shift->event, __('events.Saved.'));
    }
}
