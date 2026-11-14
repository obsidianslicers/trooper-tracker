<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison\Support;

use App\Support\OneTroopPerDay;
use Carbon\Carbon;

/**
 * Groups legacy events into the events the import creates: a linked legacy event
 * (`link` > 0) becomes a shift of its main event.
 *
 * The exception is the "one troop per day" rule. TT1.0 counted every linked event as its
 * own troop, so a linked event on the same day as another shift of its group, at a
 * different time, is imported as an event of its own when the rule covers that day. That
 * way the rule never takes a troop away from a TT1.0 sign-up. A linked event starting the
 * same minute as one already in the group still joins the group, and the import merges the
 * two into one shift.
 */
final class LegacyEventGroups
{
    /**
     * @param  iterable<object>  $events  Legacy event rows with id, link and dateStart, ordered by id then link.
     * @return array<int, list<object>> Event id => its shifts, main event first.
     */
    public static function group(iterable $events): array
    {
        $groups = [];
        $linked = [];

        foreach ($events as $event)
        {
            if ((int) $event->link > 0)
            {
                $linked[] = $event;
            }
            else
            {
                $groups[(int) $event->id] = [$event];
            }
        }

        foreach ($linked as $event)
        {
            $group_id = (int) $event->link;

            if (isset($groups[$group_id]) && self::startsOnAnotherShiftsDay($event, $groups[$group_id]))
            {
                $group_id = (int) $event->id;
            }

            $groups[$group_id][] = $event;
        }

        return $groups;
    }

    /** @param  list<object>  $group */
    private static function startsOnAnotherShiftsDay(object $event, array $group): bool
    {
        $start = Carbon::parse($event->dateStart);

        if (!OneTroopPerDay::coversStart($start))
        {
            return false;
        }

        foreach ($group as $shift)
        {
            $other = Carbon::parse($shift->dateStart);

            if ($other->isSameDay($start) && $other->format('Y-m-d H:i') !== $start->format('Y-m-d H:i'))
            {
                return true;
            }
        }

        return false;
    }
}
