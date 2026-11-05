<?php

namespace App\Helpers;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Illuminate\Support\Collection;

class ForumBBCodeHelper
{
    /**
     * Generate BBCode for a forum thread for an event.
     */
    public static function threadTemplate(Event $event, string $roster = ''): string
    {
        $bb = '[size=6][b]'.e($event->name).'[/b][/size]';

        if ($event->venue)
        {
            $bb .= "\n[size=4][i]".e($event->venue).'[/i][/size]';
        }

        $bb .= "\n\n[hr][/hr]\n";

        if ($event->venue_address)
        {
            $bb .= "\n[b]📍 Location:[/b] ".e($event->venue_address);
        }
        $bb .= "\n[b]🗓️ Schedule:[/b] ".self::schedule($event);

        if ($event->event_website)
        {
            $bb .= "\n[b]🔗 Website:[/b] ".self::website($event->event_website);
        }

        $bb .= self::detailsTable($event);

        if ($event->requested_character_types)
        {
            $bb .= "\n[b]Character Types:[/b] ".e($event->requested_character_types);
        }
        $bb .= "\n[b]Secure Changing Area:[/b] ".($event->secure_staging_area ? 'Yes' : 'No');

        if ($event->amenities)
        {
            $bb .= "\n[b]Changing Area & Amenities:[/b]";
            $bb .= "\n[quote]".e($event->amenities).'[/quote]';
        }

        $bb .= "\n[hr][/hr]";
        $bb .= "\n[size=5][b]📢 Organizer Comments[/b][/size]";
        $bb .= "\n\n[indent][size=4]".($event->comments ? e($event->comments) : 'No additional comments provided.').'[/size][/indent]';
        $bb .= "\n\n[color=#777777][i]Referred by: ".($event->referred_by ? e($event->referred_by) : 'N/A').'[/i][/color]';

        if ($roster)
        {
            $bb .= "\n\n[hr][/hr]";
            $bb .= "\n[size=5][b]👥 Event Roster[/b][/size]";
            $bb .= "\n".$roster;
        }

        // Add sign-up link
        $bb .= "\n\n[hr][/hr]";
        $bb .= "\n[center][size=6][url=\"".url('/events/details/'.$event->id).'"]👉 CLICK HERE TO SIGN UP 👈[/url][/size][/center]';

        return $bb;
    }

    /**
     * Generate a detailed, auto-updated roster for an event.
     *
     *  - Single-shift events: one table of signups in signup order.
     *  - Multi-shift events: one table per shift, each headed by the shift's
     *    time display, shifts in start-time order.
     *  - Fallback when there are no signups.
     */
    public static function rosterSummary(Event $event): string
    {
        $event->loadMissing('event_shifts.event_troopers.trooper', 'event_shifts.event_troopers.costume');

        $shifts = $event->event_shifts->sortBy(EventShift::SHIFT_STARTS_AT)->values();

        if ($shifts->every(fn (EventShift $shift) => $shift->event_troopers->isEmpty()))
        {
            return '[center]No troopers are signed up for this event.[/center]';
        }

        if ($shifts->count() === 1)
        {
            return self::rosterTable($shifts->first()->event_troopers);
        }

        $sections = [];

        foreach ($shifts as $shift)
        {
            $section = '[b][u]'.$shift->time_display."[/u][/b]\n";

            $section .= $shift->event_troopers->isEmpty()
                ? '[center]No troopers are signed up for this shift.[/center]'
                : self::rosterTable($shift->event_troopers);

            $sections[] = $section;
        }

        return implode("\n", $sections);
    }

    /**
     * The event's start and end. The end repeats the date only when it falls on another day.
     */
    private static function schedule(Event $event): string
    {
        $end_format = $event->event_start->isSameDay($event->event_end) ? 'h:i A' : 'D m/d/y h:i A';

        return $event->event_start->format('D m/d/y h:i A').' – '.$event->event_end->format($end_format);
    }

    /**
     * Link to the event's website. The field is free text, so anything that
     * isn't a plain http(s) URL is shown as text instead of a broken link.
     */
    private static function website(string $website): string
    {
        if (preg_match('#^https?://[^\s"\[\]<>]+$#i', $website))
        {
            return '[url="'.$website.'"]Click here to view[/url]';
        }

        return e($website);
    }

    /**
     * The event's short details as a three-column table, leaving out unset values.
     */
    private static function detailsTable(Event $event): string
    {
        $details = array_filter([
            'Attendees' => $event->expected_attendees,
            'Requested' => $event->requested_number_characters,
        ]) + [
            'Parking' => $event->parking_available ? 'Yes' : 'No',
            'Blasters' => $event->allow_blasters ? 'Yes' : 'No',
            'Props/Sabers' => $event->allow_props ? 'Yes' : 'No',
            'Accessible' => $event->accessible ? 'Yes' : 'No',
        ];

        $bb = "\n\n[table]";

        foreach (array_chunk($details, 3, true) as $row)
        {
            $bb .= "\n[tr]";

            foreach ($row as $label => $value)
            {
                $bb .= '[td][b]'.$label.':[/b] '.$value.'[/td]';
            }

            $bb .= str_repeat('[td][/td]', 3 - count($row)).'[/tr]';
        }

        return $bb."\n[/table]";
    }

    /**
     * Render a table with one row per signup, ordered by signup time.
     */
    private static function rosterTable(Collection $event_troopers): string
    {
        $event_troopers = $event_troopers
            ->sortBy(fn (EventTrooper $event_trooper) => $event_trooper->signed_up_at)
            ->values();

        $bb = '[table][tr][td][b]Trooper Name[/b][/td][td][b]Costume[/b][/td][td][b]Status[/b][/td][/tr]';

        foreach ($event_troopers as $event_trooper)
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

            $bb .= "\n[tr][td]".$name.'[/td][td]'.$costume_label.'[/td][td][i]'.$status_label.'[/i][/td][/tr]';
        }

        return $bb."\n[/table]";
    }
}
