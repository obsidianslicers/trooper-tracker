<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Models\Event;
use Carbon\Carbon;
use Exception;


/**
 * Service class for managing Mandalorian Mercs organization data.
 *
 * This service interacts with Google Sheets to synchronize member information,
 * update trooper statuses, and manage organization-specific data.
 */
class MandalorianMercsService extends BaseOrganizationService
{
    public function syncCostumes(): void
    {
    }

    public function syncAllMembers(): void
    {
    }

    public function syncMember(string $identifier): void
    {
    }

    public static function parseRequestAppearance(string $message): Event
    {
        $parsed = static::parseMessage($message);

        // Parse event dates (Mercs format: "12/13/2025 to 12/13/2025")
        $start_date = null;
        $end_date = null;

        if (!empty($parsed['Event Date(s)']))
        {
            $dates = explode('to', $parsed['Event Date(s)']);
            $start_date = isset($dates[0]) ? trim($dates[0]) : null;
            $end_date = isset($dates[1]) ? trim($dates[1]) : null;

            $start_date = $start_date ? Carbon::createFromFormat('m/d/Y', $start_date)->startOfDay() : null;
            $end_date = $end_date ? Carbon::createFromFormat('m/d/Y', $end_date)->startOfDay() : null;

            // Apply optional times if provided
            if ($start_date && !empty($parsed['Start time']))
            {
                try
                {
                    $time = Carbon::createFromFormat('H:i', trim($parsed['Start time']));
                    $start_date->setTime($time->hour, $time->minute);
                }
                catch (Exception $e)
                {
                    // Keep date with 00:00:00 if time parsing fails
                }
            }

            if ($end_date && !empty($parsed['End time']))
            {
                try
                {
                    $time = Carbon::createFromFormat('H:i', trim($parsed['End time']));
                    $end_date->setTime($time->hour, $time->minute);
                }
                catch (Exception $e)
                {
                    // Keep date with 00:00:00 if time parsing fails
                }
            }
        }

        // Mercs location is multi‑line: City, State, Country
        $venue_address = null;
        if (!empty($parsed['Event Location']))
        {
            $venue_address = trim(
                preg_replace('/\s+/', ' ', $parsed['Event Location'])
            );
        }

        return new Event([
            Event::CONTACT_NAME => $parsed['Name'] ?? null,
            Event::CONTACT_PHONE => $parsed['Phone'] ?? null,
            Event::CONTACT_EMAIL => $parsed['Email'] ?? null,

            Event::NAME => $parsed['Event Name'] ?? null,
            Event::VENUE => $parsed['Event Name'] ?? null, // Mercs don’t provide a separate venue name
            Event::VENUE_ADDRESS => $venue_address,

            Event::EVENT_START => $start_date,
            Event::EVENT_END => $end_date,

            Event::EVENT_WEBSITE => $parsed['Website'] ?? null,
            Event::EXPECTED_ATTENDEES => is_numeric($parsed['Number of attendees'] ?? null) ? (int) $parsed['Number of attendees'] : null,

            Event::SECURE_STAGING_AREA => ($parsed['Can provide a safe and secure changing area?'] ?? '') === 'Yes',

            Event::ALLOW_BLASTERS => ($parsed['Are our members allowed to carry prop/simulated firearms weapons such as blasters at your event?'] ?? '') === 'Yes',

            Event::ALLOW_PROPS => ($parsed['Are our members allowed to carry prop/simulated melee weapons such as axes, knives, swords, or spears at your event?'] ?? '') === 'Yes',

            Event::COMMENTS => $parsed['Event Description'] ?? null,
            Event::REFERRED_BY => $parsed['How did you hear about us?'] ?? null,

            Event::SOURCE => $message,
        ]);
    }

}
