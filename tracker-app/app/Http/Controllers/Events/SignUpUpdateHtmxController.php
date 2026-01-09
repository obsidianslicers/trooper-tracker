<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Controllers\Controller;
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
class SignUpUpdateHtmxController extends Controller
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
            $is_full = false;

            if ($event_trooper->is_handler)
            {
                $is_full = $event_trooper->event_shift->handlersMaxed();
            }
            else
            {
                $is_full = $event_trooper->event_shift->troopersMaxed();
            }

            $event_trooper->status = $request->validated('status');
            $event_trooper->save();

            if ($is_full && $event_trooper->status == EventTrooperStatus::CANCELLED)
            {
                // notify next in line that they can now attend
                $next_in_line = $event_trooper->event_shift
                    ->event_troopers()
                    ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
                    ->where(EventTrooper::IS_HANDLER, $event_trooper->is_handler)
                    ->orderBy(EventTrooper::SIGNED_UP_AT)
                    ->first();

                if ($next_in_line)
                {
                    $next_in_line->status = EventTrooperStatus::GOING;
                    $next_in_line->save();

                    Mail::to($next_in_line->trooper->email)->queue(new TrooperNextInLine($next_in_line));
                }
            }
        }
        elseif ($request->has('costume_id'))
        {
            $event_trooper->costume_id = $request->validated('costume_id');
            $event_trooper->save();
        }
        elseif ($request->has('backup_costume_id'))
        {
            $event_trooper->backup_costume_id = $request->validated('backup_costume_id');
            $event_trooper->save();
        }

        return response('ok', 200);
    }
}
