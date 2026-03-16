<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventGuestStatus;
use App\Features\Events\Commands\PromoteNextInLineEventGuestCommand;
use App\Features\Events\Commands\UpdateEventGuestCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\GuestUpdateHtmxRequest;
use App\Models\EventGuest;
use Illuminate\Http\Response;

/**
 * Handles HTMX-driven updates to event guest sign-up details.
 *
 * This controller processes updates to a guest's event participation,
 * including status changes (going, cancelled, stand-by) and costume selection.
 * When a guest cancels from a full event, it automatically promotes the next
 * stand-by guest to attending status.
 */
class GuestUpdateHtmxController extends MagicBusController
{
    /**
     * Handle the incoming HTMX request to update event guest status or costume
     *
     * Processes two types of updates:
     * - Status changes: Updates the guest's attendance status and handles waitlist promotion
     * - Costume changes: Updates the guest's selected costume for the event
     *
     * @param  GuestUpdateHtmxRequest  $request  The validated request containing status or name
     * @param  EventGuest  $event_guest  The event guest record to update
     * @return Response HTTP 200 response indicating success
     */
    public function __invoke(GuestUpdateHtmxRequest $request, EventGuest $event_guest): Response
    {
        $request->validateInputs();

        if ($request->has('status'))
        {
            $valid_data = ['status' => $request->validated('status')];

            $event_guest_cmd = new UpdateEventGuestCommand($event_guest, $valid_data);

            $this->bus->send($event_guest_cmd);
        }
        elseif ($request->has('name'))
        {
            //  costume organization ids handled via observer, so we
            //  only need to update the name on the event guest record here
            $valid_data = [
                EventGuest::NAME => $request->validated('name'),
            ];

            $event_guest_cmd = new UpdateEventGuestCommand($event_guest, $valid_data);

            $this->bus->send($event_guest_cmd);
        }

        return response('ok', 200);
    }
}
