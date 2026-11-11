<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Support;

use App\Models\Event;
use Carbon\CarbonInterface;

/**
 * The title of an event's forum thread, used when the thread is created and each time
 * it is brought up to date, so a changed date or name reaches the forum too.
 *
 * The title starts with the event's date ("2026/03/14 Sat - Hospital Visit"). XenForo
 * can't sort threads by an event's date, but it can sort a forum by title, so an event
 * forum sorted by title lists upcoming events soonest first.
 *
 * A name that already starts with the event's date ("11/14 Parade", "Sat, Nov 14 - Parade")
 * is used as it is, so the date isn't shown twice.
 */
final class ForumThreadTitle
{
    private const WEEKDAY = '(?:mon|tue|wed|thu|fri|sat|sun)[a-z]*\.?,?\s+';

    private const MONTHS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    private const MONTH_NAME = '(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?'
        .'|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';

    public static function for(Event $event): string
    {
        $name = (string) $event->name;

        if ($event->event_start === null || self::startsWithDate($name, $event->event_start))
        {
            return $name;
        }

        return $event->event_start->format('Y/m/d D').' - '.$name;
    }

    /**
     * Whether the name starts with this date, optionally after a weekday. Only the event's own
     * date counts, so "May 4th Celebration" on another day, or a name whose date went stale
     * when the event moved, still gets the date in front.
     */
    private static function startsWithDate(string $name, CarbonInterface $date): bool
    {
        $name = preg_replace('/^\s*(?:'.self::WEEKDAY.')?/i', '', $name);

        // 2026/11/14, 2026-11-14
        if (preg_match('~^(\d{4})[/.-](\d{1,2})[/.-](\d{1,2})(?!\d)~', $name, $m))
        {
            return self::matches($date, (int) $m[2], (int) $m[3], $m[1]);
        }

        // 11/14, 11/14/26, 11/14/2026
        if (preg_match('~^(\d{1,2})[/.-](\d{1,2})(?:[/.-](\d{4}|\d{2}))?(?!\d)~', $name, $m))
        {
            return self::matches($date, (int) $m[1], (int) $m[2], $m[3] ?? '');
        }

        // Nov 14, Nov. 14th, November 14, 2026
        if (preg_match('/^'.self::MONTH_NAME.'\.?\s+(\d{1,2})(?:st|nd|rd|th)?(?![\da-z])(?:,?\s+(\d{4})(?!\d))?/i', $name, $m))
        {
            return self::matches($date, self::MONTHS[strtolower(substr($m[1], 0, 3))], (int) $m[2], $m[3] ?? '');
        }

        return false;
    }

    private static function matches(CarbonInterface $date, int $month, int $day, string $year): bool
    {
        if ($month !== $date->month || $day !== $date->day)
        {
            return false;
        }

        return match (strlen($year))
        {
            0 => true,
            2 => (int) $year === $date->year % 100,
            default => (int) $year === $date->year,
        };
    }
}
