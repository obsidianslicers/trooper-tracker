<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handler for signing up a trooper for an event shift.
 *
 * Creates an EventTrooper record with GOING status, linking the trooper
 * to the specified event shift and tracking who added the sign-up.
 *
 * @implements CommandHandlerInterface<SignUpEventTrooperCommand>
 */
readonly class SignUpEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to sign up a trooper for an event shift.
     *
     * Creates a new EventTrooper record with:
     * - GOING status (confirmed attendance)
     * - Link to event shift
     * - Link to trooper
     * - Tracking of who added the sign-up
     *
     * @param SignUpEventTrooperCommand $message The command containing shift, trooper, and added_by info.
     * @return EventTrooper The created EventTrooper record.
     */
    public function __invoke(object $message): mixed
    {
        /** @var SignUpEventTrooperCommand $message */
        $event_trooper = new EventTrooper();

        $event_trooper->event_shift_id = $message->event_shift->id;
        $event_trooper->trooper_id = $message->trooper->id;
        $event_trooper->is_handler = $message->trooper->is_handler;
        $event_trooper->signed_up_at = now();
        $event_trooper->added_by_trooper_id = $message->added_by_trooper->id == $message->trooper->id ? null : $message->added_by_trooper->id;
        $status = EventTrooperStatus::GOING;

        if ($event_trooper->is_handler)
        {
            if ($message->event_shift->handlersMaxed())
            {
                $status = EventTrooperStatus::STAND_BY;
            }
        }
        else
        {
            if ($message->event_shift->troopersMaxed())
            {
                $status = EventTrooperStatus::STAND_BY;
            }
        }

        $event_trooper->status = $status;
        $event_trooper->save();

        return $event_trooper;
    }
}
