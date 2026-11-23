<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\SignUpChangeOutcome;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Http\Requests\Events\Commands\UpdateGuestSignUpRequest;
use App\Messages\Events\Commands\ChangeEventGuestStatus;
use App\Messages\Events\Commands\UpdateEventGuest;
use App\Models\EventGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

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

        if ($request->has('status'))
        {
            $status = $request->validated('status');

            $outcome = ChangeEventGuestStatus::call(
                event_guest: $event_guest,
                status: $status,
                changed_by_trooper: $request->user(),
            );

            if ($outcome === SignUpChangeOutcome::FORBIDDEN)
            {
                return response('Forbidden', 403);
            }

            return $this->backToEvent($event_guest->event_shift->event, $status === EventGuestStatus::CANCELLED->value
                ? __('events.Sign-up cancelled.')
                : __('events.Saved.'));
        }

        if ($request->has('name'))
        {
            //  costume organization ids handled via observer, so we
            //  only need to update the name on the event guest record here
            UpdateEventGuest::call(event_guest: $event_guest, valid_data: [
                EventGuest::NAME => $request->validated('name'),
            ]);
        }

        return $this->backToEvent($event_guest->event_shift->event, __('events.Saved.'));
    }
}
