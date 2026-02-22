<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Mail\Events\TrooperNextInLine;
use App\Models\EventTrooper;
use Illuminate\Support\Facades\Mail;

/**
 * Handler for promoting the next standby trooper to confirmed attendance.
 *
 * Searches for the next trooper with STAND_BY status for the same shift,
 * promotes them to GOING status, and sends them a TrooperNextInLine email notification.
 *
 * @implements CommandHandlerInterface<PromoteNextInLineEventTrooperCommand>
 */
readonly class PromoteNextInLineEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to promote the next standby trooper.
     *
     * Workflow:
     * 1. Find the next trooper with STAND_BY status for the same shift
     * 2. Match handler/member role of cancelled trooper
     * 3. Order by sign-up time (first in gets promoted)
     * 4. Update status to GOING
     * 5. Send notification email
     *
     * @param  PromoteNextInLineEventTrooperCommand  $message  The command with the cancelled trooper info.
     * @return null Always returns null.
     */
    public function __invoke(object $message): mixed
    {
        $next_in_line = $message->event_trooper->event_shift
            ->event_troopers()
            ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
            ->where(EventTrooper::IS_HANDLER, $message->event_trooper->is_handler)
            ->orderBy(EventTrooper::SIGNED_UP_AT)
            ->first();

        if ($next_in_line !== null)
        {
            $next_in_line->status = EventTrooperStatus::GOING;

            $next_in_line->save();

            Mail::to($next_in_line->trooper->email)->queue(new TrooperNextInLine($next_in_line));
        }

        return null;
    }
}
