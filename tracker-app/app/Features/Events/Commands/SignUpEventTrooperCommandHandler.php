<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Enums\RosterAction;
use App\Jobs\CreateTrooperFriendshipJob;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Notifications\Events\TrooperSignedUpNotification;
use App\Services\EventRosterCapacityService;

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
    use ShouldBeTransactional;

    public function __construct(private EventRosterCapacityService $capacity) {}

    /**
     * @param  SignUpEventTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $message->event_shift->loadMissing('event_shift_stations');

        $event_trooper = new EventTrooper;

        $costume = $message->costume_id !== null ? Costume::find($message->costume_id) : null;

        $event_trooper->event_shift_id = $message->event_shift->id;
        $event_trooper->event_shift_station_id = $message->event_shift_station_id;
        $event_trooper->trooper_id = $message->trooper->id;
        $event_trooper->organization_id = $message->organization_id;
        $event_trooper->is_handler = $costume !== null ? $costume->countsAsHandler() : $message->is_handler;
        $event_trooper->signed_up_at = now();
        $event_trooper->added_by_trooper_id = $message->added_by_trooper->id == $message->trooper->id ? null : $message->added_by_trooper->id;
        $status = EventTrooperStatus::GOING;

        if ($message->event_shift->event->status === EventStatus::MANUAL_SELECTION)
        {
            $status = EventTrooperStatus::STAND_BY;
        }

        if ($status !== EventTrooperStatus::STAND_BY)
        {
            $can_go = $this->capacity->canGo(
                $message->event_shift,
                $message->organization_id,
                $message->event_shift_station_id,
                $event_trooper->is_handler,
                lock: true,
            );

            if (!$can_go)
            {
                $status = EventTrooperStatus::STAND_BY;
            }
        }

        if ($costume !== null)
        {
            $event_trooper->costume_id = $costume->id;
            $org_ids = $costume->approvedOrgIdsForTrooper($message->trooper->id);
            $event_trooper->costume_organization_ids = !empty($org_ids) ? $org_ids : null;
        }

        if ($status === EventTrooperStatus::GOING && $event_trooper->needsCostumeBeforeGoing())
        {
            $status = EventTrooperStatus::PENDING;
        }

        $event_trooper->status = $status;

        $event_trooper->save();

        if ($event_trooper->added_by_trooper_id !== null)
        {
            $moderator = $message->added_by_trooper->membership_role === MembershipRole::MODERATOR ||
                $message->added_by_trooper->membership_role === MembershipRole::ADMINISTRATOR;

            if (!$moderator)
            {
                //  don't create a friendship if the added by trooper is a moderator or administrator
                dispatch(new CreateTrooperFriendshipJob($event_trooper->added_by_trooper_id, $event_trooper->trooper_id));
            }
        }

        $message->trooper->notify(new TrooperSignedUpNotification($event_trooper));

        dispatch(new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::SIGNED_UP));

        return null;
    }
}
