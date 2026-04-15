<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventGuestStatus;
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
        return $this->full_date_display . ' - ' .
            $this->shift_starts_at->format('g:ia') . ' - ' .
            $this->shift_ends_at->format('g:ia');
    }

    /**
     * Get a formatted date display string for the shift.
     *
     * Format: "Sat - Oct 03, 2026"
     *
     * @return string
     */
    public function getFullDateDisplayAttribute(): string
    {
        //Sat - Oct 03, 2026 
        return $this->shift_starts_at->format('D') . ' - ' .
            $this->shift_starts_at->format('M d, Y');
    }

    /**
     * Get a compact formatted time display string for the shift.
     *
     * Format: "2-4pm" or "11am-1pm"
     *
     * @return string
     */
    public function getCompactTimeDisplayAttribute(): string
    {
        $start = $this->shift_starts_at;
        $end = $this->shift_ends_at;

        // Check if we need to show 'am/pm' on the start time
        // If start and end are both 'pm', we only show it on the end for brevity
        $startFormat = ($start->format('a') === $end->format('a')) ? 'g' : 'ga';

        // Remove :00 from times to keep it tight (e.g., 2:00pm becomes 2pm)
        $start_time = str_replace(':00', '', $start->format($startFormat));
        $end_time = str_replace(':00', '', $end->format('ga'));

        return $start_time . '-' . $end_time;
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
        return $this->hasTrooperSignedUp($trooper->id);
    }

    private function hasTrooperSignedUp(int $trooper_id): bool
    {
        return $this->event_troopers->where(EventTrooper::TROOPER_ID, $trooper_id)->isNotEmpty();
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
     * If shifts_allowed is set on the event, also checks that the trooper hasn't
     * exceeded the maximum number of shifts they can attend for this event.
     *
     * @param Trooper $trooper The trooper attempting to sign up
     * @return bool True if the trooper can sign up
     */
    public function canSignUp(Trooper $trooper): bool
    {
        if ($this->is_open)
        {
            // Require mission brief acknowledgement when configured on the event
            if ($this->event->require_mission_brief_ack && !$this->event->hasMissionBriefAcknowledgementFor($trooper))
            {
                return false;
            }

            // For minor troopers, also check if the guardian is attending if
            // the event does not allow minors without guardians
            // default to true for non-minors and minors with no guardian
            // requirement, so it doesn't block sign-up in those cases
            $guardian_attending = true;

            if ($trooper->is_minor)
            {
                if ($this->event->minorCannotAttend())
                {
                    return false;
                }

                $guardian_attending = $this->isGuardianAttending($trooper);

                if (!$guardian_attending)
                {
                    return false;
                }
            }

            $already_signed_up = $this->isSignedUp($trooper);

            if ($already_signed_up)
            {
                return false;
            }

            if ($this->event->shifts_allowed === null)
            {
                return $guardian_attending;
            }

            $count = $this->event->getShiftCountFor($trooper);

            return $guardian_attending && $count < $this->event->shifts_allowed;
        }

        return false;
    }

    /**
     * Check if a guardian is attending this shift for a minor trooper.
     *
     * @param Trooper $trooper The minor trooper to check
     * @return bool True if the guardian is attending
     */
    private function isGuardianAttending(Trooper $trooper): bool
    {
        if ($trooper->is_minor)
        {
            return $this->hasTrooperSignedUp($trooper->guardian_id);
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
    public function canSignUpTrooper(Trooper $trooper): bool
    {
        if ($this->is_open)
        {
            if ($this->isGoing($trooper))
            {
                return $this->hasRemainingFriendSlots($trooper);
            }
        }

        return false;
    }

    /**
     * Check if there are remaining friend slots for a trooper on this shift.
     *
     * Returns true if friends_allowed is null (unlimited) or if the trooper
     * has added fewer friends than the limit. Does not check if the trooper
     * is going — use this alongside canSignUpTrooper or $can_moderate.
     *
     * @param Trooper $trooper The trooper to check remaining slots for
     * @return bool True if the trooper has remaining friend slots
     */
    public function hasRemainingFriendSlots(Trooper $trooper): bool
    {
        $friends_allowed = $this->event->friends_allowed;

        if ($friends_allowed === null)
        {
            return true;
        }

        if ($friends_allowed === 0)
        {
            return false;
        }

        $friends = $this->event_troopers()
            ->where(EventTrooper::ADDED_BY_TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, '!=', EventTrooperStatus::CANCELLED)
            ->count();

        return $friends < $friends_allowed;
    }

    /**
     * Check if there are remaining guest slots for a trooper on this shift.
     *
     * Returns true if guests_allowed is null (unlimited) or if the trooper
     * has added fewer guests than the limit. Does not check if the trooper
     * is going — use this alongside canSignUpGuest or $can_moderate.
     *
     * @param Trooper $trooper The trooper to check remaining slots for
     * @return bool True if the trooper has remaining guest slots
     */
    public function hasRemainingGuestSlots(Trooper $trooper): bool
    {
        $guests_allowed = $this->event->guests_allowed;

        if ($guests_allowed === null)
        {
            return true;
        }

        if ($guests_allowed === 0)
        {
            return false;
        }

        $guests = $this->event_guests()
            ->where(EventGuest::ADDED_BY_TROOPER_ID, $trooper->id)
            ->where(EventGuest::STATUS, '!=', EventGuestStatus::CANCELLED)
            ->count();

        return $guests < $guests_allowed;
    }

    /**
     * Check if a trooper can sign up a friend for this shift.
     *
     * A trooper can sign up a guest if the shift is open and they are already signed up.
     *
     * @param Trooper $trooper The trooper attempting to sign up a guest
     * @return bool True if the trooper can sign up a guest
     */
    public function canSignUpGuest(Trooper $trooper): bool
    {
        if ($this->is_open)
        {
            if ($this->isGoing($trooper))
            {
                return $this->hasRemainingGuestSlots($trooper);
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
