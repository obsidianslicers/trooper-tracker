<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Enums\EventTrooperStatus;
use App\Events\TrooperSignupStatusChanged;
use App\Facades\TroopTrackerFacade;
use App\Jobs\UpdateEventForumThreadJob;
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
    public function created(EventTrooper $event_trooper): void
    {
        $this->queueForumThreadSync($event_trooper);
    }

    public function updated(EventTrooper $event_trooper): void
    {
        if ($event_trooper->wasChanged([
            EventTrooper::STATUS,
            EventTrooper::COSTUME_ID,
        ]))
        {
            $this->queueForumThreadSync($event_trooper);
        }

        $this->dispatchStatusChange($event_trooper);
    }

    public function deleted(EventTrooper $event_trooper): void
    {
        $this->queueForumThreadSync($event_trooper);
    }

    public function restored(EventTrooper $event_trooper): void
    {
        $this->queueForumThreadSync($event_trooper);
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

        TrooperSignupStatusChanged::dispatch(
            $event_trooper,
            $previous instanceof EventTrooperStatus ? $previous : EventTrooperStatus::tryFrom((string) $previous),
        );
    }

    private function queueForumThreadSync(EventTrooper $event_trooper): void
    {
        if (! TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        $event_trooper->loadMissing('event_shift.event');

        $event_id = $event_trooper->event_shift?->event?->getKey();

        if ($event_id === null)
        {
            return;
        }

        dispatch(new UpdateEventForumThreadJob($event_id));
    }
}
