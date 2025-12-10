<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Base\Event as BaseEvent;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        ]);
    }

    /**
     * Get the display string for the event time.
     *
     * @return string
     */
    public function timeDisplay(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->event_start->format('D') . ' - ' .
            $this->event_start->format('M d, Y') . ' - ' .
            $this->event_start->format('g:ia') . ' - ' .
            $this->event_end->format('g:ia');
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
        $event = self::parseEmail($body, $source_format);

        if ((int) $event->requested_characters > 0)
        {
            $event->has_organization_limits = true;
            $event->troopers_allowed = $event->requested_characters;
            $event->handlers_allowed = null;
        }

        return $event;
    }

    /**
     * Parse an email body and return an EventRequest object.
     *
     * @param string $body
     * @param string $source_format
     * @return \App\Models\Event
     */
    private static function parseEmail(string $body, string $source_format): static
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

        // Hydrate an EventRequest object WITHOUT saving
        return new static([
            'contact_name' => $parsed['Contact Name'] ?? null,
            'contact_phone' => $parsed['Contact Phone Number'] ?? null,
            'contact_email' => $parsed['Contact Email'] ?? null,
            'name' => $parsed['Event Name'] ?? null,
            'venue' => $parsed['Venue'] ?? null,
            'venue_address' => $parsed['Venue address'] ?? null,
            'event_start' => isset($parsed['Event Start'])
                ? Carbon::createFromFormat('m/d/Y - Hi', $parsed['Event Start'])
                : null,
            'event_end' => isset($parsed['Event End'])
                ? Carbon::createFromFormat('m/d/Y - Hi', $parsed['Event End'])
                : null,
            'event_website' => $parsed['Event Website'] ?? null,
            'expected_attendees' => $parsed['Expected number of attendees'] ?? null,
            'requested_characters' => $parsed['Requested number of characters'] ?? null,
            'requested_character_types' => $parsed['Requested character types'] ?? null,
            'secure_staging_area' => ($parsed['Secure changing/staging area'] ?? '') === 'Yes',
            'allow_blasters' => ($parsed['Can troopers carry blasters'] ?? '') === 'Yes',
            'allow_props' => ($parsed['Can troopers carry/bring props like lightsabers and staffs'] ?? '') === 'Yes',
            'parking_available' => ($parsed['Is parking available'] ?? '') === 'Yes',
            'accessible' => ($parsed['Is venue accessible to those with limited mobility'] ?? '') === 'Yes',
            'amenities' => $parsed['Amenities available at venue'] ?? null,
            'comments' => $parsed['Comments'] ?? null,
            'referred_by' => $parsed['Referred by'] ?? null,
            'source' => $body
        ]);
    }
}