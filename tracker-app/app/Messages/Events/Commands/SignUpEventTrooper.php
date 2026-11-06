<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Enums\RosterAction;
use App\Events\TrooperSignedUp;
use App\Jobs\CreateTrooperFriendshipJob;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\TrooperSignedUpNotification;
use App\Services\EventRosterCapacityService;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Command to sign up a trooper for an event shift.
 *
 * Creates an EventTrooper record linking a trooper to a specific event shift.
 * Tracks who added the trooper (for admin sign-ups vs self sign-ups).
 *
 * @method static void call(EventShift $event_shift, Trooper $trooper, Trooper $added_by_trooper, ?int $organization_id = null, bool $is_handler = false, ?int $costume_id = null, ?int $event_shift_station_id = null)
 */
final class SignUpEventTrooper extends Message
{
    use ShouldBeTransactional;

    /**
     * Create a new command instance.
     *
     * @param  EventShift  $event_shift  The event shift to sign up for.
     * @param  Trooper  $trooper  The trooper signing up for the event.
     * @param  Trooper  $added_by_trooper  The trooper who added this sign-up (may be same as $trooper).
     * @param  int|null  $organization_id  The top-level organization the trooper is trooping for.
     */
    public function __construct(
        private readonly EventShift $event_shift,
        private readonly Trooper $trooper,
        private readonly Trooper $added_by_trooper,
        private readonly ?int $organization_id = null,
        private readonly bool $is_handler = false,
        private readonly ?int $costume_id = null,
        private readonly ?int $event_shift_station_id = null,
    ) {}

    public function handle(EventRosterCapacityService $capacity): void
    {
        $this->event_shift->loadMissing('event_shift_stations');

        $event_trooper = $this->findOrNewEventTrooper();

        if ($event_trooper === null)
        {
            return;
        }

        $costume = $this->costume_id !== null ? Costume::find($this->costume_id) : null;

        $event_trooper->event_shift_id = $this->event_shift->id;
        $event_trooper->event_shift_station_id = $this->event_shift_station_id;
        $event_trooper->trooper_id = $this->trooper->id;
        $event_trooper->organization_id = $this->organization_id;
        $event_trooper->is_handler = $costume !== null ? $costume->countsAsHandler() : $this->is_handler;
        $event_trooper->signed_up_at = now();
        $event_trooper->added_by_trooper_id = $this->added_by_trooper->id == $this->trooper->id ? null : $this->added_by_trooper->id;
        $status = EventTrooperStatus::GOING;

        if ($this->event_shift->event->status === EventStatus::MANUAL_SELECTION)
        {
            $status = EventTrooperStatus::STAND_BY;
        }

        if ($status !== EventTrooperStatus::STAND_BY)
        {
            $can_go = $capacity->canGo(
                $this->event_shift,
                $this->organization_id,
                $this->event_shift_station_id,
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
            $org_ids = $costume->approvedOrgIdsForTrooper($this->trooper->id);
            $event_trooper->costume_organization_ids = !empty($org_ids) ? $org_ids : null;
        }

        if ($status === EventTrooperStatus::GOING && $event_trooper->needsCostumeBeforeGoing())
        {
            $status = EventTrooperStatus::PENDING;
        }

        $event_trooper->status = $status;

        if ($event_trooper->trashed())
        {
            $event_trooper->restore();
        }
        else
        {
            $event_trooper->save();
        }

        if ($event_trooper->added_by_trooper_id !== null)
        {
            $moderator = $this->added_by_trooper->membership_role === MembershipRole::MODERATOR ||
                $this->added_by_trooper->membership_role === MembershipRole::ADMINISTRATOR;

            if (!$moderator)
            {
                //  don't create a friendship if the added by trooper is a moderator or administrator
                dispatch(new CreateTrooperFriendshipJob($event_trooper->added_by_trooper_id, $event_trooper->trooper_id));
            }
        }

        $this->trooper->notify(new TrooperSignedUpNotification($event_trooper));

        TrooperSignedUp::dispatch($event_trooper);

        dispatch(new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::SIGNED_UP));
    }

    /**
     * The (event_shift_id, trooper_id) unique index ignores soft deletes, so a trooper who
     * was removed from a shift must have their old row restored rather than a new one inserted.
     * Returns null when the trooper already has an active row (e.g. a double-submitted signup).
     */
    private function findOrNewEventTrooper(): ?EventTrooper
    {
        $existing = EventTrooper::withTrashed()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->event_shift->id)
            ->where(EventTrooper::TROOPER_ID, $this->trooper->id)
            ->lockForUpdate()
            ->first();

        if ($existing === null)
        {
            return new EventTrooper;
        }

        if (!$existing->trashed())
        {
            return null;
        }

        $existing->costume_id = null;
        $existing->costume_organization_ids = null;
        $existing->backup_costume_id = null;
        $existing->backup_costume_organization_ids = null;
        $existing->is_attending_without_costume = false;

        return $existing;
    }
}
