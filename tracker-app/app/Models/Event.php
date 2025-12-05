<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Models\Base\Event as BaseEvent;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends BaseEvent
{
    use HasFilter;
    use HasFactory;
    use HasEventScopes;
    use HasTrooperStamps;

    /**
     * Get the troopers signed up for the event.
     */
    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }

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
     * Get the display string for the event time.
     *
     * @return string
     */
    public function timeDisplay(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->starts_at->format('D') . ' - ' .
            $this->starts_at->format('M d, Y') . ' - ' .
            $this->starts_at->format('g:ia') . ' - ' .
            $this->ends_at->format('g:ia');
    }

    /**
     * Create a new Event instance from an email body.
     *
     * @param string $body
     * @param string $source
     * @return \App\Models\Event
     */
    public static function fromEmail(string $body, string $source = '501'): Event
    {
        $event_request = self::parseEmail($body, $source);

        $event = new Event();

        $event->name = $event_request->event_name;
        $event->starts_at = $event_request->event_start;
        $event->ends_at = $event_request->event_end;

        if ($event_request->expected_attendees > 0)
        {
            $event->limit_organizations = true;
            $event->troopers_allowed = $event_request->expected_attendees;
            $event->handlers_allowed = null;
        }

        $event->event_request = $event_request;

        return $event;
    }

    /**
     * Parse an email body and return an EventRequest object.
     *
     * @param string $body
     * @param string $resource
     * @return \App\Models\EventRequest
     */
    private static function parseEmail(string $body, string $resource): EventRequest
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
        return new EventRequest([
            'contact_name' => $parsed['Contact Name'] ?? null,
            'contact_phone' => $parsed['Contact Phone Number'] ?? null,
            'contact_email' => $parsed['Contact Email'] ?? null,
            'event_name' => $parsed['Event Name'] ?? null,
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

    // // Example usage:
// $emailBody = <<<EOT
// Contact Name: Matthew Drennan
// Contact Phone Number: (407) 967-1441
// Contact Email: drennanmattheww@gmail.com
// Event Name: Project Kid Connect
// Venue: Lake County Sheriff's South District Office
// Venue address: 15855 State Rte 50 Clermont, Florida 34711
// Clermont, Florida
// 34711
// USA
// Event Start: 07/12/2025 - 0900
// Event End: 07/12/2025 - 1200
// Event Website:
// Expected number of attendees: 1000
// Requested number of characters: 100
// Requested character types:
// Secure changing/staging area: Yes
// Can troopers carry blasters: Yes
// Can troopers carry/bring props like lightsabers and staffs: Yes
// Is parking available: Yes
// Is venue accessible to those with limited mobility: Yes
// Amenities available at venue: Water, snacks, and a booth will be provided.
// Comments: Community event with law enforcement and children and we give away school supplies.
// Referred by: Matt Drennan TK52233
// EOT;

    // print_r(parseEmailBody($emailBody));
}
