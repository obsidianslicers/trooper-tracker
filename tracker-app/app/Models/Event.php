<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Base\Event as BaseEvent;
use App\Models\Casts\SanitizeHtmlCast;
use App\Models\Casts\EnforceZeroCast;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
use Spatie\CalendarLinks\Link;

/**
 * Represents a trooping event or appearance.
 *
 * Events are organized activities where members participate in costume to support
 * charitable causes, community events, or other activities. Each event has multiple
 * shifts, tracks attendance, and includes details about the venue, contact information,
 * and participation requirements.
 */
class Event extends BaseEvent
{
    use HasFilter;
    use HasFactory;
    use HasEventScopes;
    use HasTrooperStamps;
    use HasObserver;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::TYPE => EventType::class,
            self::STATUS => EventStatus::class,
            self::NAME => SanitizeHtmlCast::class,
            self::CONTACT_NAME => SanitizeHtmlCast::class,
            self::CONTACT_PHONE => SanitizeHtmlCast::class,
            self::CONTACT_EMAIL => SanitizeHtmlCast::class,
            self::VENUE => SanitizeHtmlCast::class,
            self::VENUE_ADDRESS => SanitizeHtmlCast::class,
            self::EVENT_WEBSITE => SanitizeHtmlCast::class,
            self::REQUESTED_CHARACTER_TYPES => SanitizeHtmlCast::class,
        ]);
    }

    /**
     * Get the organization that owns this event.
     *
     * @return BelongsTo<Organization, self>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, self::ORGANIZATION_ID);
    }

    /**
     * Get the primary (top-level) organization for this event.
     *
     * @return BelongsTo<Organization, self>
     */
    public function primary_organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, self::PRIMARY_ORGANIZATION_ID);
    }

    /**
     * Get all troopers associated with this event through event shifts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function troopers(): HasManyThrough
    {
        // hasManyThrough: Event -> EventShift -> EventTrooper
        return $this->hasManyThrough(EventTrooper::class, EventShift::class);
    }

    /**
     * Get all organizations associated with this event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function event_organizations(): HasMany
    {
        return $this->hasMany(EventOrganization::class);
    }

    public function watches(): HasMany
    {
        return $this->hasMany(EventWatch::class);
    }

    /**
     * Whether this event has any capacity limits (global or per-org).
     */
    public function hasLimits(): bool
    {
        if ($this->troopers_allowed !== null || $this->handlers_allowed !== null)
        {
            return true;
        }

        $this->loadMissing('event_organizations');

        return $this->event_organizations->contains(
            fn($o) => $o->troopers_allowed !== null || $o->handlers_allowed !== null
        );
    }

    /**
     * Get a formatted time display string for the event.
     *
     * Format: "Sat - Oct 03, 2026 - 2:00pm - 4:00pm"
     *
     * @return string
     */
    public function getTimeDisplayAttribute(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->full_date_display . ' - ' .
            $this->event_start->format('g:ia') . ' - ' .
            $this->event_end->format('g:ia');
    }

    /**
     * Get a formatted date display string for the event.
     *
     * Format: "Sat - Oct 03, 2026"
     *
     * @return string
     */
    public function getFullDateDisplayAttribute(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->event_start->format('D') . ' - ' .
            $this->event_start->format('M d, Y');
    }

    /**
     * Get a compact formatted time display string for the event.
     *
     * Format: "2-4pm" or "11am-1pm"
     *
     * @return string
     */
    public function getCompactTimeDisplayAttribute(): string
    {
        $start = $this->event_start;
        $end = $this->event_end;

        // Check if we need to show 'am/pm' on the start time
        // If start and end are both 'pm', we only show it on the end for brevity
        $startFormat = ($start->format('a') === $end->format('a')) ? 'g' : 'ga';

        // Remove :00 from times to keep it tight (e.g., 2:00pm becomes 2pm)
        $start_time = str_replace(':00', '', $start->format($startFormat));
        $end_time = str_replace(':00', '', $end->format('ga'));

        return $start_time . '-' . $end_time;
    }

    /**
     * Check if the event is open for sign-ups.
     *
     * @return bool
     */
    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, [EventStatus::OPEN, EventStatus::MANUAL_SELECTION], true);
    }

    /**
     * Check if the event is open for sign-ups.
     *
     * @return bool
     */
    public function getIsClosedAttribute(): bool
    {
        return $this->status === EventStatus::CLOSED;
    }

    /**
     * Check if the event sign-ups are locked.
     *
     * @return bool
     */
    public function getIsLockedAttribute(): bool
    {
        return $this->status === EventStatus::SIGN_UP_LOCKED;
    }

    /**
     * Check if the event is in draft status.
     *
     * @return bool
     */
    public function getIsDraftAttribute(): bool
    {
        return $this->status === EventStatus::DRAFT;
    }

    /**
     * Check if the event is active (draft, open, or locked).
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        switch ($this->status)
        {
            case EventStatus::DRAFT:
            case EventStatus::OPEN:
            case EventStatus::MANUAL_SELECTION:
            case EventStatus::SIGN_UP_LOCKED:
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if trooper status updates are allowed for this event.
     *
     * Updates are allowed while the event is active or within the recent
     * grace period after the event ends.
     *
     * @return bool
     */
    public function getCanUpdateTrooperStatusAttribute(): bool
    {
        return $this->is_active || $this->is_within_grace_period;
    }

    /**
     * Check if the event is within the recent grace period after it ends.
     *
     * @return bool
     */
    public function getIsWithinGracePeriodAttribute(): bool
    {
        return $this->event_end->isPast() && $this->event_end->isAfter(now()->subDays(30));
    }

    /**
     * Check if the event is at risk (starts within 5 days but has no troopers signed up).
     *
     * @return bool
     */
    public function getAtRiskAttribute(): bool
    {
        if ($this->is_active)
        {
            $starts_soon = $this->event_start->lte(Carbon::now()->addDays(5));

            if ($starts_soon)
            {
                return $this->event_shifts->sum('event_troopers_count') == 0;
            }
        }

        return false;
    }

    /**
     * Determine whether moderation actions are allowed for a closed event.
     *
     * Moderation is allowed only when the event is closed, still within the
     * post-event grace period, and the caller has moderation permission.
     *
     * @param bool $can_moderate Whether the trooper has moderation permission.
     *
     * @return bool True when moderation is allowed for this closed event.
     */
    public function canModerateIfClosed(bool $can_moderate): bool
    {
        return $can_moderate && $this->is_within_grace_period;
    }

    /**
     * Get the number of shifts a trooper is signed up for in this event.
     *
     * This method counts how many shifts within this event the specified trooper
     * has signed up for, regardless of their status (going, standby, tentative, etc.).
     * Ensures event_shifts and event_troopers relationships are loaded before counting.
     *
     * @param Trooper $trooper The trooper to count shifts for
     * @return int The number of shifts the trooper is signed up for
     */
    public function getShiftCountFor(Trooper $trooper): int
    {
        // Load event_shifts if missing
        $this->loadMissing('event_shifts');

        // Load event_troopers for each shift if missing
        $this->event_shifts->loadMissing('event_troopers');

        return $this->event_shifts
            ->filter(fn($shift) => $shift->event_troopers->contains('trooper_id', $trooper->id))
            ->count();
    }

    /**
     * Determine if the given trooper has acknowledged the mission brief for this event.
     */
    public function hasMissionBriefAcknowledgementFor(Trooper $trooper): bool
    {
        return DB::table('tt_event_mission_acks')
            ->where('event_id', $this->id)
            ->where('trooper_id', $trooper->id)
            ->exists();
    }

    /**
     * Determine if a minor trooper can attend this event based on associated organizations.
     * 
     * A minor trooper can attend if at least one of the event's associated organizations has
     * the requires_guardian flag set to true. If no organizations require a guardian, then a
     * minor trooper cannot attend this event.
     * 
     * @return bool True if a minor trooper can attend, false otherwise
     */
    public function minorAllowedToAttend(): bool
    {
        $this->loadMissing('organizations');

        return $this->organizations->contains(fn($org) => $org->requires_guardian);
    }

    /**
     * Determine if a minor trooper cannot attend this event based on associated organizations.
     * 
     * A minor trooper cannot attend if none of the event's associated organizations have
     * the requires_guardian flag set to true. If at least one organization requires a guardian,
     * then a minor trooper can attend this event.
     * 
     * @return bool True if a minor trooper cannot attend, false otherwise
     */
    public function minorCannotAttend(): bool
    {
        return $this->minorAllowedToAttend() === false;
    }

    // /**
    //  * Check if a trooper can sign up for this event.
    //  *
    //  * A trooper can sign up if the event is open and they are not already signed up.
    //  * If shifts_allowed is set on the event, also checks that the trooper hasn't
    //  * exceeded the maximum number of shifts they can attend for this event.
    //  *
    //  * @param Trooper $trooper The trooper attempting to sign up
    //  * @return bool True if the trooper can sign up
    //  */
    // public function canSignUp(Trooper $trooper): bool
    // {
    //     if ($this->is_open)
    //     {
    //         if ($trooper->is_minor && !$this->hasOrganizationWithRequiresGuardian())
    //         {
    //             // For minor troopers, if ZERO organizations require a guardian, then
    //             // a minor cannot sign up (ie Galactic Academy can_attend=false)
    //             return false;
    //         }

    //         $count = $this->getShiftCountFor($trooper);

    //         if ($this->shifts_allowed !== null)
    //         {
    //             return $count < $this->shifts_allowed;
    //         }

    //         return $count == 0;
    //     }

    //     return false;
    // }

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

        $from = $this->event_start->copy()->shiftTimezone($timezone);
        $to = $this->event_end->copy()->shiftTimezone($timezone);

        $name = $this->name;
        $location = $this->venue_address;
        $description = 'Troop Tracker Event';

        // Create link
        $link = Link::create($name, $from, $to)->description($description);

        if ($location)
        {
            $link = $link->address($location);
        }

        return $link;
    }
}