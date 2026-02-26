<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Base\Event as BaseEvent;
use App\Models\Casts\SanitizeHtmlCast;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
            self::CHARITY_NAME => SanitizeHtmlCast::class,
            self::CONTACT_NAME => SanitizeHtmlCast::class,
            self::CONTACT_PHONE => SanitizeHtmlCast::class,
            self::CONTACT_EMAIL => SanitizeHtmlCast::class,
            self::VENUE => SanitizeHtmlCast::class,
            self::VENUE_ADDRESS => SanitizeHtmlCast::class,
            self::VENUE_CITY => SanitizeHtmlCast::class,
            self::VENUE_STATE => SanitizeHtmlCast::class,
            self::VENUE_ZIP => SanitizeHtmlCast::class,
            self::VENUE_COUNTRY => SanitizeHtmlCast::class,
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
        return $this->status === EventStatus::OPEN;
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
        return $this->is_active || $this->event_end->isAfter(now()->subDays(30));
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
}