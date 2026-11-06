<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Enums\EventTrooperStatus;
use App\Events\TrooperSignupStatusChanged;
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
