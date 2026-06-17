<?php

declare(strict_types=1);

namespace App\Models\Observers;

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
    }

    public function deleted(EventTrooper $event_trooper): void
    {
        $this->queueForumThreadSync($event_trooper);
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
