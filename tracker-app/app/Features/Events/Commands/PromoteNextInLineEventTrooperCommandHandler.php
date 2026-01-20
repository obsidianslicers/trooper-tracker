<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Mail\Events\TrooperNextInLine;
use App\Models\EventTrooper;
use Illuminate\Support\Facades\Mail;

/**
 * Handler for updating trooper profile information.
 *
 * Fills the trooper model with validated data and saves.
 * If complete_setup flag is true, sets the setup_completed_at timestamp
 * to mark the trooper's initial profile setup as complete.
 *
 * @implements CommandHandlerInterface<PromoteNextInLineEventTrooperCommand>
 */
readonly class PromoteNextInLineEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update trooper profile.
     *
     * @param PromoteNextInLineEventTrooperCommand $message The command with trooper and update data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        /** @var PromoteNextInLineEventTrooperCommand $message */

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

