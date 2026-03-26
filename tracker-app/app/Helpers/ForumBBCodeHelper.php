<?php

namespace App\Helpers;

use App\Enums\EventTrooperStatus;
use App\Models\Event;

class ForumBBCodeHelper
{
    /**
     * Generate BBCode for a forum thread for an event.
     */
    public static function threadTemplate(Event $event, string $roster = ''): string
    {
        $bb = '';
        $bb .= "\n[b]Event Name:[/b] ".e($event->name);
        $bb .= "\n[b]Venue:[/b] ".e($event->venue);
        $bb .= "\n[b]Venue address:[/b] ".e($event->venue_address);
        $bb .= "\n[b]Event Start:[/b] ".$event->event_start->format('m/d/y h:i A');
        $bb .= "\n[b]Event End:[/b] ".$event->event_end->format('m/d/y h:i A');

        // Add more fields as needed, e.g. website, attendees, amenities, comments, referred by, etc.
        if ($event->event_website)
        {
            $bb .= "\n[b]Event Website:[/b] ".e($event->event_website);
        }
        if ($event->expected_attendees)
        {
            $bb .= "\n[b]Expected number of attendees:[/b] ".$event->expected_attendees;
        }
        if ($event->requested_number_characters)
        {
            $bb .= "\n[b]Requested number of characters:[/b] ".$event->requested_number_characters;
        }
        if ($event->requested_character_types)
        {
            $bb .= "\n[b]Requested character types:[/b] ".e($event->requested_character_types);
        }
        if ($event->secure_staging_area !== null)
        {
            $bb .= "\n[b]Secure changing/staging area:[/b] ".($event->secure_staging_area ? 'Yes' : 'No');
        }
        if ($event->allow_blasters !== null)
        {
            $bb .= "\n[b]Can troopers carry blasters:[/b] ".($event->allow_blasters ? 'Yes' : 'No');
        }
        if ($event->allow_props !== null)
        {
            $bb .= "\n[b]Can troopers carry/bring props like lightsabers and staffs:[/b] ".($event->allow_props ? 'Yes' : 'No');
        }
        if ($event->parking_available !== null)
        {
            $bb .= "\n[b]Is parking available:[/b] ".($event->parking_available ? 'Yes' : 'No');
        }
        if ($event->accessible !== null)
        {
            $bb .= "\n[b]Is venue accessible to those with limited mobility:[/b] ".($event->accessible ? 'Yes' : 'No');
        }
        if ($event->amenities)
        {
            $bb .= "\n[b]Amenities available at venue:[/b] ".e($event->amenities);
        }
        $bb .= "\n[b]Comments:[/b]\n".($event->comments ? e($event->comments) : 'No comments for this event.');
        $bb .= "\n[b]Referred by:[/b] ".($event->referred_by ?? 'Not available');

        if ($roster)
        {
            $bb .= "\n".$roster;
        }

        // Add sign-up link
        $bb .= "\n[b][u]Sign Up / Event Roster:[/u][/b]\n";
        $bb .= '[url]'.url('/events/'.$event->id).'[/url]';

        return $bb;
    }

    /**
     * Generate a detailed, auto-updated roster listing for an event.
     *
     * Mirrors the legacy behaviour:
     *  - Header: [b]Roster:[/b]
     *  - One line per signup in signup order:
     *      -[i]Status[/i]: Name (Costume)
     *  - Fallback when there are no signups.
     */
    public static function rosterSummary(Event $event): string
    {
        $event->loadMissing('event_shifts.event_troopers.trooper', 'event_shifts.event_troopers.costume');

        $troopers = collect();

        foreach ($event->event_shifts as $shift)
        {
            foreach ($shift->event_troopers as $event_trooper)
            {
                $troopers->push($event_trooper);
            }
        }

        // Sort globally by signup time to approximate the legacy signuptime ordering.
        $troopers = $troopers->sortBy(function ($event_trooper) {
            return $event_trooper->signed_up_at;
        })->values();

        $bb = '[b]Roster:[/b]';

        if ($troopers->isEmpty())
        {
            $bb .= "\n-No troopers are signed up for this event.";

            return $bb;
        }

        foreach ($troopers as $event_trooper)
        {
            $status = $event_trooper->status instanceof EventTrooperStatus
                ? $event_trooper->status
                : EventTrooperStatus::from((string) $event_trooper->status);

            $status_label = to_title($status->name)->toString();

            $name = $event_trooper->trooper?->display_name ?? 'Unknown Trooper';

            if ($event_trooper->is_handler)
            {
                $costume_label = 'Handler';
            }
            elseif ($event_trooper->costume)
            {
                $costume_label = $event_trooper->costume->name;
            }
            else
            {
                $costume_label = 'No Costume Selected';
            }

            $bb .= "\n-[i]".$status_label.'[/i]: '.$name.' ('.$costume_label.')';
        }

        return $bb;
    }
}
