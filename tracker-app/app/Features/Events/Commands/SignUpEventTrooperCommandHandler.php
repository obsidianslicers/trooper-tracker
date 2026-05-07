<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Jobs\CreateTrooperFriendshipJob;
use App\Models\EventTrooper;
use App\Notifications\Events\TrooperSignedUpNotification;

/**
 * Handler for signing up a trooper for an event shift.
 *
 * Creates an EventTrooper record with GOING or STAND_BY status depending on
 * event type and capacity, then dispatches a notification.
 *
 * @implements CommandHandlerInterface<SignUpEventTrooperCommand>
 */
readonly class SignUpEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  SignUpEventTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $event_trooper = new EventTrooper;

        $event_trooper->event_shift_id = $message->event_shift->id;
        $event_trooper->trooper_id = $message->trooper->id;
        $event_trooper->is_handler = $message->trooper->is_handler;
        $event_trooper->signed_up_at = now();
        $event_trooper->added_by_trooper_id = $message->added_by_trooper->id == $message->trooper->id ? null : $message->added_by_trooper->id;
        $status = EventTrooperStatus::GOING;

        if ($message->event_shift->event->status === EventStatus::MANUAL_SELECTION)
        {
            $status = EventTrooperStatus::STAND_BY;
        }

        if ($status !== EventTrooperStatus::STAND_BY && $event_trooper->is_handler)
        {
            if ($message->event_shift->handlersMaxed())
            {
                $status = EventTrooperStatus::STAND_BY;
            }
        }
        elseif ($status !== EventTrooperStatus::STAND_BY)
        {
            if ($message->event_shift->troopersMaxed())
            {
                $status = EventTrooperStatus::STAND_BY;
            }
        }

        $event_trooper->status = $status;
        $event_trooper->save();

        if ($event_trooper->added_by_trooper_id !== null)
        {
            dispatch(new CreateTrooperFriendshipJob($event_trooper->added_by_trooper_id, $event_trooper->trooper_id));
        }

        $message->trooper->notify(new TrooperSignedUpNotification($event_trooper));

        return null;
    }
}
