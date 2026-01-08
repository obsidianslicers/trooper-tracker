<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Base\EventShift as BaseEventShift;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventShiftScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\CalendarLinks\Link;

/**
 * Represents a specific time shift within an event.
 *
 * Events can be divided into multiple shifts with different start and end times.
 * Each shift tracks its own trooper assignments, capacity limits, and status.
 * This allows for better management of events that span multiple time periods
 * or require different groups of participants.
 */
class EventShift extends BaseEventShift
{
    use HasEventShiftScopes;
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::STATUS => EventStatus::class,
        ]);
    }

    /**
     * Check if the shift is open for sign-ups.
     *
     * A shift is open if both the parent event is open and the shift status is OPEN.
     *
     * @return bool True if the shift is open
     */
    public function getIsOpenAttribute(): bool
    {
        if (!$this->event->is_open)
        {
            return false;
        }
        return $this->status === EventStatus::OPEN;
    }

    /**
     * Check if the shift sign-ups are locked.
     *
     * A shift is locked if either the parent event is locked or the shift status is SIGN_UP_LOCKED.
     *
     * @return bool True if the shift is locked
     */
    public function getIsLockedAttribute(): bool
    {
        if ($this->event->is_locked)
        {
            return true;
        }
        return $this->status === EventStatus::SIGN_UP_LOCKED;
    }

    /**
     * Get a formatted display string for the shift time.
     *
     * Format: "Sat - Oct 03, 2026 - 2:00pm - 4:00pm"
     *
     * @return string The formatted time display
     */
    public function getTimeDisplayAttribute(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->shift_starts_at->format('D') . ' - ' .
            $this->shift_starts_at->format('M d, Y') . ' - ' .
            $this->shift_starts_at->format('g:ia') . ' - ' .
            $this->shift_ends_at->format('g:ia');
    }

    /**
     * Get a shortened formatted display string for the shift time.
     *
     * Format: "10/03 - 2:00pm - 4:00pm"
     *
     * @return string The shortened time display
     */
    public function getShortTimeDisplayAttribute(): string
    {
        //10/03 - 2:00pm - 4:00pm
        return $this->shift_starts_at->format('m/d - g:ia') . ' - ' .
            $this->shift_ends_at->format('g:ia');
    }

    /**
     * Check if the trooper capacity for this shift is full.
     *
     * @return bool True if the number of signed up troopers meets or exceeds the allowed limit
     */
    public function troopersMaxed(): bool
    {
        $troopers_allowed = $this->event->troopers_allowed;

        if ($troopers_allowed === null)
        {
            return false;
        }

        $troopers_signed_up = $this->event_troopers()->troopers()->count();

        return $troopers_signed_up >= $troopers_allowed;
    }

    /**
     * Check if the handler capacity for this shift is full.
     *
     * @return bool True if the number of signed up handlers meets or exceeds the allowed limit
     */
    public function handlersMaxed(): bool
    {
        $handlers_allowed = $this->event->handlers_allowed;

        if ($handlers_allowed === null)
        {
            return false;
        }

        $handlers_signed_up = $this->event_troopers()->handlers()->count();

        return $handlers_signed_up >= $handlers_allowed;
    }

    /**
     * Check if a specific trooper is signed up for this shift.
     *
     * @param Trooper $trooper The trooper to check
     * @return bool True if the trooper is signed up
     */
    public function isSignedUp(Trooper $trooper): bool
    {
        return $this->event_troopers->where(EventTrooper::TROOPER_ID, $trooper->id)->isNotEmpty();
    }

    /**
     * Check if a specific trooper is going to this shift.
     *
     * @param Trooper $trooper The trooper to check
     * @return bool True if the trooper is going
     */
    public function isGoing(Trooper $trooper): bool
    {
        // we assume event_troopers relationship is loaded for the UI's sake
        return $this->event_troopers
            ->where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING)
            ->isNotEmpty();
    }

    /**
     * Check if a trooper can sign up for this shift.
     *
     * A trooper can sign up if the shift is open and they are not already signed up.
     *
     * @param Trooper $trooper The trooper attempting to sign up
     * @return bool True if the trooper can sign up
     */
    public function canSignUp(Trooper $trooper): bool
    {
        if ($this->is_open)
        {
            return !$this->isSignedUp($trooper);
        }

        return false;
    }

    /**
     * Check if a trooper can sign up a friend for this shift.
     *
     * A trooper can sign up a friend if the shift is open and they are already signed up.
     *
     * @param Trooper $trooper The trooper attempting to sign up a friend
     * @return bool True if the trooper can sign up a friend
     */
    public function canSignUpFriend(Trooper $trooper): bool
    {
        if ($this->is_open)
        {
            if ($this->isGoing($trooper))
            {
                $friends_allowed = $this->event->friends_allowed;

                if ($friends_allowed === null)
                {
                    return true;
                }

                $friends = $this->event_troopers()
                    ->where(EventTrooper::ADDED_BY_TROOPER_ID, $trooper->id)
                    ->count();

                if ($friends < $friends_allowed)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Create a calendar link for this shift.
     *
     * Generates a calendar link that can be added to various calendar applications
     * (Google Calendar, iCal, Outlook, etc.) with the shift details.
     *
     * @return Link The calendar link object
     */
    public function createCalendarLink(): Link
    {
        $timezone = config('tracker.calendar.timezone');

        $from = $this->shift_starts_at->copy()->shiftTimezone($timezone);
        $to = $this->shift_ends_at->copy()->shiftTimezone($timezone);

        $name = $this->event->name;
        $location = $this->event->venue_address;
        $description = 'Troop Tracker Event';

        // Create link
        $link = Link::create($name, $from, $to)
            ->description($description)
            ->address($location);

        return $link;
    }
}
