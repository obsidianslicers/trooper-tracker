<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Enums\EventTrooperStatus;
use App\Events\TrooperSignupStatusChanged;
use App\Messages\Events\Commands\ForgetAttendanceReminders;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;

/**
 * Handles lifecycle events for the EventTrooper model.
 *
 * Credit attribution is handled explicitly by the signup, roster update, and
 * shift-complete flows. The observer is intentionally limited to side effects
 * that need to happen after roster lifecycle changes.
 */
class EventTrooperObserver
{
    public function updated(EventTrooper $event_trooper): void
    {
        $this->dispatchStatusChange($event_trooper);
        $this->forgetAnsweredReminders($event_trooper);

        $still_eligible = in_array($event_trooper->status, Event::COORDINATOR_STATUSES, true)
            && !$event_trooper->trashed()
            && !$event_trooper->wasChanged([EventTrooper::EVENT_SHIFT_ID, EventTrooper::TROOPER_ID]);

        if (!$still_eligible && $event_trooper->wasChanged([EventTrooper::STATUS, EventTrooper::EVENT_SHIFT_ID, EventTrooper::TROOPER_ID, EventTrooper::DELETED_AT]))
        {
            $this->releaseCoordinator($event_trooper);
        }
    }

    public function deleted(EventTrooper $event_trooper): void
    {
        $this->releaseCoordinator($event_trooper);
        ForgetAttendanceReminders::call(event_trooper: $event_trooper);
    }

    /**
     * Once a sign-up is answered (attended, couldn't make it, sent for verification) or changed
     * by a moderator, its "did you make it?" reminder has nothing left to ask.
     */
    private function forgetAnsweredReminders(EventTrooper $event_trooper): void
    {
        $moved = $event_trooper->wasChanged([EventTrooper::EVENT_SHIFT_ID, EventTrooper::TROOPER_ID]);

        if (!$moved && !$event_trooper->wasChanged([EventTrooper::STATUS, EventTrooper::REQUESTED_ORGANIZATION_IDS]))
        {
            return;
        }

        if (!$moved && $event_trooper->intendsToGo() && !$event_trooper->isAwaitingVerification())
        {
            return;
        }

        ForgetAttendanceReminders::call(event_trooper: $event_trooper);
    }

    /**
     * A picked event coordinator who is no longer going or attended stops being it, and the
     * event's creator coordinates again.
     */
    private function releaseCoordinator(EventTrooper $event_trooper): void
    {
        $shift_ids = array_filter([
            $event_trooper->event_shift_id,
            $event_trooper->getOriginal(EventTrooper::EVENT_SHIFT_ID),
        ]);

        Event::releaseIneligibleCoordinators(
            EventShift::withTrashed()->whereIn(EventShift::ID, $shift_ids)->pluck(EventShift::EVENT_ID)
        );
    }

    /**
     * A restore is a new signup (TrooperSignedUp covers it), not a status change.
     */
    private function dispatchStatusChange(EventTrooper $event_trooper): void
    {
        if (!$event_trooper->wasChanged(EventTrooper::STATUS)
            || $event_trooper->wasChanged(EventTrooper::DELETED_AT))
        {
            return;
        }

        $previous = $event_trooper->getOriginal(EventTrooper::STATUS);

        if (!$previous instanceof EventTrooperStatus)
        {
            $previous = EventTrooperStatus::tryFrom((string) $previous);
        }

        TrooperSignupStatusChanged::dispatch($event_trooper, $previous);
    }
}
