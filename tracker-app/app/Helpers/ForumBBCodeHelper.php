<?php

namespace App\Helpers;

use App\Models\Event;

class ForumBBCodeHelper
{
    /**
     * Generate BBCode for a forum thread for an event.
     *
     * @param Event $event
     * @param string $roster
     * @return string
     */
    public static function threadTemplate(Event $event, string $roster = ''): string
    {
        $bb = '';
        $bb .= "\n[b]Event Name:[/b] " . e($event->name);
        $bb .= "\n[b]Venue:[/b] " . e($event->venue);
        $bb .= "\n[b]Venue address:[/b] " . e($event->venue_address);
        $bb .= "\n[b]Event Start:[/b] " . $event->event_start->format('m/d/y h:i A');
        $bb .= "\n[b]Event End:[/b] " . $event->event_end->format('m/d/y h:i A');

        // Add more fields as needed, e.g. website, attendees, amenities, comments, referred by, etc.
        if ($event->event_website) {
            $bb .= "\n[b]Event Website:[/b] " . e($event->event_website);
        }
        if ($event->expected_attendees) {
            $bb .= "\n[b]Expected number of attendees:[/b] " . $event->expected_attendees;
        }
        if ($event->requested_number_characters) {
            $bb .= "\n[b]Requested number of characters:[/b] " . $event->requested_number_characters;
        }
        if ($event->requested_character_types) {
            $bb .= "\n[b]Requested character types:[/b] " . e($event->requested_character_types);
        }
        if ($event->secure_staging_area !== null) {
            $bb .= "\n[b]Secure changing/staging area:[/b] " . ($event->secure_staging_area ? 'Yes' : 'No');
        }
        if ($event->allow_blasters !== null) {
            $bb .= "\n[b]Can troopers carry blasters:[/b] " . ($event->allow_blasters ? 'Yes' : 'No');
        }
        if ($event->allow_props !== null) {
            $bb .= "\n[b]Can troopers carry/bring props like lightsabers and staffs:[/b] " . ($event->allow_props ? 'Yes' : 'No');
        }
        if ($event->parking_available !== null) {
            $bb .= "\n[b]Is parking available:[/b] " . ($event->parking_available ? 'Yes' : 'No');
        }
        if ($event->accessible !== null) {
            $bb .= "\n[b]Is venue accessible to those with limited mobility:[/b] " . ($event->accessible ? 'Yes' : 'No');
        }
        if ($event->amenities) {
            $bb .= "\n[b]Amenities available at venue:[/b] " . e($event->amenities);
        }
        $bb .= "\n[b]Comments:[/b]\n" . ($event->comments ? e($event->comments) : 'No comments for this event.');
        $bb .= "\n[b]Referred by:[/b] " . ($event->referred_by ?? 'Not available');

        if ($roster) {
            $bb .= "\n" . $roster;
        }

        // Add sign-up link
        $bb .= "\n[b][u]Sign Up / Event Roster:[/u][/b]\n";
        $bb .= "[url]" . url('/events/' . $event->id) . "[/url]";

        return $bb;
    }
}
