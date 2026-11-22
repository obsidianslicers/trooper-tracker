<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use App\Notifications\Events\EventShiftCompletedNotification;
use Hyperdrive\Message;

/**
 * Deletes the "did you make it?" reminders a trooper has in their notification log for one
 * sign-up: before a new one is sent, so there's one per troop, and once it's answered.
 *
 * Reminders written before they carried the sign-up's id are matched by their link, which
 * named the event and shift.
 *
 * @method static int call(EventTrooper $event_trooper)
 */
final class ForgetAttendanceReminders extends Message
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
    ) {}

    /**
     * @return int How many were deleted.
     */
    public function handle(): int
    {
        $event_trooper = $this->event_trooper;
        $shift_id = $event_trooper->getOriginal(EventTrooper::EVENT_SHIFT_ID) ?? $event_trooper->event_shift_id;
        $trooper_id = $event_trooper->getOriginal(EventTrooper::TROOPER_ID) ?? $event_trooper->trooper_id;
        $event_id = $event_trooper->event_shift?->event_id;

        return TrooperNotification::query()
            ->where(TrooperNotification::TYPE, EventShiftCompletedNotification::class)
            ->where(TrooperNotification::NOTIFIABLE_TYPE, (new Trooper)->getMorphClass())
            ->where(TrooperNotification::NOTIFIABLE_ID, $trooper_id)
            ->where(function ($query) use ($event_trooper, $shift_id, $event_id)
            {
                $query->where(TrooperNotification::DATA.'->'.EventShiftCompletedNotification::EVENT_TROOPER_ID, $event_trooper->id);

                if ($event_id !== null)
                {
                    $query->orWhere(TrooperNotification::DATA.'->url', "/events/details/{$event_id}#shift-{$shift_id}");
                }
            })
            ->delete();
    }
}
