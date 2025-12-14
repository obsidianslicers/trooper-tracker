<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Base\Event as BaseEvent;
use App\Models\Casts\SanitizeHtmlCast;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Event extends BaseEvent
{
    use HasFilter;
    use HasFactory;
    use HasEventScopes;
    use HasTrooperStamps;

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

    public function troopers(): HasManyThrough
    {
        // hasManyThrough: Event -> EventShift -> EventTrooper
        return $this->hasManyThrough(EventTrooper::class, EventShift::class);
    }

    public function getTimeDisplayAttribute(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->event_start->format('D') . ' - ' .
            $this->event_start->format('M d, Y') . ' - ' .
            $this->event_start->format('g:ia') . ' - ' .
            $this->event_end->format('g:ia');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === EventStatus::OPEN;
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->status === EventStatus::SIGN_UP_LOCKED;
    }

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
     * Create a new Event instance from an email body.
     *
     * @param string $body
     * @param string $source_format
     * @return \App\Models\Event
     */
    public static function fromEmail(string $body, string $source_format = '501st'): static
    {
        $lines = preg_split("/\r\n|\n|\r/", $body);
        $parsed = [];
        $currentKey = null;

        foreach ($lines as $line)
        {
            $line = trim($line);
            if ($line === '')
            {
                continue; // skip empty lines
            }

            if (strpos($line, ':') !== false)
            {
                // New identifier line
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);

                $currentKey = $key;
                $parsed[$currentKey] = $value;
            }
            else
            {
                // Continuation of previous value
                if ($currentKey !== null)
                {
                    $parsed[$currentKey] .= ' ' . $line;
                }
            }
        }
        return new static([
            self::CONTACT_NAME => $parsed['Contact Name'] ?? null,
            self::CONTACT_PHONE => $parsed['Contact Phone Number'] ?? null,
            self::CONTACT_EMAIL => $parsed['Contact Email'] ?? null,
            self::NAME => $parsed['Event Name'] ?? null,
            self::VENUE => $parsed['Venue'] ?? null,
            self::VENUE_ADDRESS => $parsed['Venue address'] ?? null,
            self::EVENT_START => isset($parsed['Event Start']) ? Carbon::createFromFormat('m/d/Y - Hi', $parsed['Event Start']) : null,
            self::EVENT_END => isset($parsed['Event End']) ? Carbon::createFromFormat('m/d/Y - Hi', $parsed['Event End']) : null,
            self::EVENT_WEBSITE => $parsed['Event Website'] ?? null,
            self::EXPECTED_ATTENDEES => $parsed['Expected number of attendees'] ?? null,
            self::REQUESTED_CHARACTERS => $parsed['Requested number of characters'] ?? null,
            self::REQUESTED_CHARACTER_TYPES => $parsed['Requested character types'] ?? null,
            self::SECURE_STAGING_AREA => ($parsed['Secure changing/staging area'] ?? '') === 'Yes',
            self::ALLOW_BLASTERS => ($parsed['Can troopers carry blasters'] ?? '') === 'Yes',
            self::ALLOW_PROPS => ($parsed['Can troopers carry/bring props like lightsabers and staffs'] ?? '') === 'Yes',
            self::PARKING_AVAILABLE => ($parsed['Is parking available'] ?? '') === 'Yes',
            self::ACCESSIBLE => ($parsed['Is venue accessible to those with limited mobility'] ?? '') === 'Yes',
            self::AMENITIES => $parsed['Amenities available at venue'] ?? null,
            self::COMMENTS => $parsed['Comments'] ?? null,
            self::REFERRED_BY => $parsed['Referred by'] ?? null,
            self::SOURCE => $body,
        ]);
    }
}