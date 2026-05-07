<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Notifications\Events\TrooperPromotedToGoingNotification;

/**
 * Handler for promoting the next standby trooper to confirmed attendance.
 *
 * @implements CommandHandlerInterface<PromoteNextInLineEventTrooperCommand>
 */
readonly class PromoteNextInLineEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  PromoteNextInLineEventTrooperCommand  $message
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

            $next_in_line->trooper->notify(new TrooperPromotedToGoingNotification($next_in_line));
        }

        return null;
    }
}
