<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Support;

use App\Models\Event;

/**
 * The title of an event's forum thread, used when the thread is created and each time
 * it is brought up to date, so a changed date or name reaches the forum too.
 *
 * With the "date_in_thread_titles" setting on, the title starts with the event's date
 * ("2026/03/14 Sat - Hospital Visit"). XenForo can't sort threads by an event's date,
 * but it can sort a forum by title, so a garrison that does gets its upcoming events
 * listed soonest first.
 */
final class ForumThreadTitle
{
    public static function for(Event $event): string
    {
        if (!config('plugins.xenforo.date_in_thread_titles', false) || $event->event_start === null)
        {
            return (string) $event->name;
        }

        return $event->event_start->format('Y/m/d D').' - '.$event->name;
    }
}
