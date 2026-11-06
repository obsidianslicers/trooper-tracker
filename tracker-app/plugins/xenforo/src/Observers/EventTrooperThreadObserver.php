<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Observers;

use App\Models\EventTrooper;
use TroopTracker\Plugins\Xenforo\Jobs\UpdateEventThreadJob;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

/**
 * Refreshes the roster in an event's forum thread when its sign-ups change.
 */
class EventTrooperThreadObserver
{
    public function created(EventTrooper $event_trooper): void
    {
        $this->queueThreadSync($event_trooper);
    }

    public function updated(EventTrooper $event_trooper): void
    {
        if ($event_trooper->wasChanged([EventTrooper::STATUS, EventTrooper::COSTUME_ID]))
        {
            $this->queueThreadSync($event_trooper);
        }
    }

    public function deleted(EventTrooper $event_trooper): void
    {
        $this->queueThreadSync($event_trooper);
    }

    public function restored(EventTrooper $event_trooper): void
    {
        $this->queueThreadSync($event_trooper);
    }

    private function queueThreadSync(EventTrooper $event_trooper): void
    {
        if (!XenforoService::isConfigured())
        {
            return;
        }

        $event_trooper->loadMissing('event_shift.event');

        $event_id = $event_trooper->event_shift?->event?->getKey();

        if ($event_id !== null && EventThread::linked($event_id)?->hasFirstPost())
        {
            dispatch(new UpdateEventThreadJob($event_id));
        }
    }
}
