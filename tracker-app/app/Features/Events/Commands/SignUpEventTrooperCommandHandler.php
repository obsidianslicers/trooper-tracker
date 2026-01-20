<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;

/**
 * Handler for creating event notifications and sending instant emails to individual troopers.
 *
 * Creates EventNotification records for eligible troopers and immediately
 * queues emails for those with INSTANT notification preferences. Troopers
 * with DAILY preferences have unprocessed notifications created for batch
 * processing by the daily digest command.
 *
 * @implements CommandHandlerInterface<SignUpEventTrooperCommand>
 */
class SignUpEventTrooperCommandHandler
{
    /**
     * Create notification record and send instant email if applicable.
     *
     * Workflow:
     * 1. Validate trooper has a valid email address
     * 2. Create EventNotification record
     * 3. If trooper has INSTANT preference:
     *    - Mark notification as processed immediately
     *    - Queue InstantEventNotification email
     * 4. If trooper has DAILY preference:
     *    - Leave notification unprocessed for batch processing
     *
     * @param SignUpEventTrooperCommand $message The command containing event and trooper
     * @return EventTrooper The created EventTrooper record
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
