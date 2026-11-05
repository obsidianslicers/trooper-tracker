<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Observers;

use App\Facades\TroopTrackerFacade;
use App\Models\Event;
use TroopTracker\Plugins\Xenforo\Jobs\DeleteEventThreadJob;
use TroopTracker\Plugins\Xenforo\Jobs\UpdateEventThreadJob;
use TroopTracker\Plugins\Xenforo\Models\EventThread;

/**
 * Keeps an event's forum thread in step with the event.
 */
class EventThreadObserver
{
    /**
     * Fields shown in the thread's first post.
     */
    private const THREAD_FIELDS = [
        Event::NAME,
        Event::VENUE,
        Event::VENUE_ADDRESS,
        Event::EVENT_START,
        Event::EVENT_END,
        Event::EVENT_WEBSITE,
        Event::EXPECTED_ATTENDEES,
        Event::REQUESTED_NUMBER_CHARACTERS,
        Event::REQUESTED_CHARACTER_TYPES,
        Event::SECURE_STAGING_AREA,
        Event::ALLOW_BLASTERS,
        Event::ALLOW_PROPS,
        Event::PARKING_AVAILABLE,
        Event::ACCESSIBLE,
        Event::AMENITIES,
        Event::COMMENTS,
        Event::REFERRED_BY,
    ];

    public function updated(Event $event): void
    {
        if (!$event->wasChanged(self::THREAD_FIELDS) || !TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        if (EventThread::linked($event->id)?->hasFirstPost())
        {
            dispatch(new UpdateEventThreadJob($event->id));
        }
    }

    public function deleted(Event $event): void
    {
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        $thread = EventThread::linked($event->id);

        if ($thread !== null)
        {
            dispatch(new DeleteEventThreadJob($event->id, (int) $thread->thread_id))->afterCommit();
        }
    }
}
