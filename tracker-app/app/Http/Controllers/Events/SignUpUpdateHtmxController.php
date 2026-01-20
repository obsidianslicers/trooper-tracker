<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Features\Events\Queries\GetNextTrooperInLineForEventQuery;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\SetupUpdateHtmxRequest;
use App\Mail\Events\TrooperNextInLine;
use App\Models\EventTrooper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

/**
 * Handles HTMX-driven updates to event trooper sign-up details.
 *
 * This controller processes updates to a trooper's event participation,
 * including status changes (going, cancelled, stand-by) and costume selection.
 * When a trooper cancels from a full event, it automatically promotes the next
 * stand-by trooper to attending status.
 */
class SignUpUpdateHtmxController extends MagicBusController
{
    /**
     * Handle the incoming HTMX request to update event trooper status or costume.
     *
     * Processes two types of updates:
     * - Status changes: Updates the trooper's attendance status and handles waitlist promotion
     * - Costume changes: Updates the trooper's selected costume for the event
     *
     * @param SetupUpdateHtmxRequest $request The validated request containing status or costume_id
     * @param EventTrooper $event_trooper The event trooper record to update
     * @return Response HTTP 200 response indicating success
     */
    public function __invoke(SetupUpdateHtmxRequest $request, EventTrooper $event_trooper): Response
    {
        $request->validateInputs();

        if ($request->has('status'))
        {
            $is_full = $event_trooper->is_handler
                ? $event_trooper->event_shift->handlersMaxed()
                : $event_trooper->event_shift->troopersMaxed();

            $valid_data = ['status' => $request->validated('status')];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

            $this->bus->send($event_trooper_cmd);

            if ($is_full && $event_trooper->status == EventTrooperStatus::CANCELLED)
            {
                // notify next in line that they can now attend
                $next_in_line_cmd = new PromoteNextInLineEventTrooperCommand($event_trooper);

                $this->bus->send($next_in_line_cmd);
            }
        }
        elseif ($request->has('costume_id'))
        {
            $valid_data = ['costume_id' => $request->validated('costume_id')];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

            $this->bus->send($event_trooper_cmd);
        }
        elseif ($request->has('backup_costume_id'))
        {
            $valid_data = ['backup_costume_id' => $request->validated('backup_costume_id')];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

            $this->bus->send($event_trooper_cmd);
        }

        return response('ok', 200);
    }
}
