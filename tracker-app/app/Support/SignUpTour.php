<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;

/**
 * The event page's "How sign-up works" tour (tracker.signup_tour).
 *
 * The page decides whether the tour starts by itself; the browser remembers that it was seen
 * (localStorage, per trooper and device), so there's nothing to store on the server. The
 * button above the roster replays it whatever the setting.
 */
final class SignUpTour
{
    /** Troopers who haven't attended a troop yet. The default. */
    public const NEW_TROOPERS = 'new_troopers';

    public const EVERYONE = 'everyone';

    public const OFF = 'off';

    /**
     * Whether the tour starts by itself for this trooper on this event: only where there's a
     * shift open for sign-up, so it has something to point at.
     */
    public static function startsFor(?Trooper $trooper, Event $event): bool
    {
        if ($trooper === null || !$event->event_shifts->contains(fn ($event_shift) => $event_shift->is_open))
        {
            return false;
        }

        return match (config('tracker.signup_tour', self::NEW_TROOPERS))
        {
            self::EVERYONE => true,
            self::NEW_TROOPERS => !self::hasAttended($trooper),
            default => false,
        };
    }

    /**
     * Any attended sign-up, verified or not, on any kind of event: they've been through
     * signing up before.
     */
    private static function hasAttended(Trooper $trooper): bool
    {
        return EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $trooper->id)
            ->whereIn(EventTrooper::STATUS, [EventTrooperStatus::ATTENDED, EventTrooperStatus::AWAITING_VERIFICATION])
            ->exists();
    }
}
