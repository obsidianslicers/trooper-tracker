<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Hyperdrive\Message;

/**
 * Query for the holidays shown on the events calendar, between two dates.
 *
 * Which holidays depends on `tracker.calendar.holidays` (`TRACKER_CALENDAR_HOLIDAYS`):
 * `us` (the default) gives the US federal holidays plus widely observed days that shape
 * trooping (Easter, Mother's Day, Halloween and so on); `none` turns them off. Dates are
 * the day itself, not the weekday a federal holiday is observed on.
 *
 * @method static array<string, list<string>> call(CarbonInterface $from, CarbonInterface $to)
 */
final class GetHolidays extends Message
{
    public const US = 'us';

    /**
     * @param  CarbonInterface  $from  First day to include
     * @param  CarbonInterface  $to  Last day to include
     */
    public function __construct(
        private readonly CarbonInterface $from,
        private readonly CarbonInterface $to,
    ) {}

    /**
     * @return array<string, list<string>> Holiday names by date (Y-m-d), in date order
     */
    public function handle(): array
    {
        if (config('tracker.calendar.holidays') !== self::US)
        {
            return [];
        }

        $from = $this->from->toDateString();
        $to = $this->to->toDateString();

        $holidays = [];

        for ($year = $this->from->year; $year <= $this->to->year; $year++)
        {
            foreach ($this->usHolidays($year) as [$date, $name])
            {
                $key = $date->toDateString();

                if ($key >= $from && $key <= $to)
                {
                    $holidays[$key][] = $name;
                }
            }
        }

        ksort($holidays);

        return $holidays;
    }

    /**
     * @return list<array{CarbonImmutable, string}>
     */
    private function usHolidays(int $year): array
    {
        $day = fn (int $month, int $day) => CarbonImmutable::create($year, $month, $day);
        $nth = fn (int $month, int $nth, int $weekday) => CarbonImmutable::create($year, $month, 1)->nthOfMonth($nth, $weekday);
        $last = fn (int $month, int $weekday) => CarbonImmutable::create($year, $month, 1)->lastOfMonth($weekday);

        return [
            [$day(1, 1), "New Year's Day"],
            [$nth(1, 3, CarbonInterface::MONDAY), 'Martin Luther King Jr. Day'],
            [$day(2, 14), "Valentine's Day"],
            [$nth(2, 3, CarbonInterface::MONDAY), "Presidents' Day"],
            [$day(3, 17), "St. Patrick's Day"],
            [$this->easter($year), 'Easter'],
            [$nth(5, 2, CarbonInterface::SUNDAY), "Mother's Day"],
            [$last(5, CarbonInterface::MONDAY), 'Memorial Day'],
            [$nth(6, 3, CarbonInterface::SUNDAY), "Father's Day"],
            [$day(6, 19), 'Juneteenth'],
            [$day(7, 4), 'Independence Day'],
            [$nth(9, 1, CarbonInterface::MONDAY), 'Labor Day'],
            [$nth(10, 2, CarbonInterface::MONDAY), 'Columbus Day'],
            [$day(10, 31), 'Halloween'],
            [$day(11, 11), 'Veterans Day'],
            [$nth(11, 4, CarbonInterface::THURSDAY), 'Thanksgiving'],
            [$day(12, 24), 'Christmas Eve'],
            [$day(12, 25), 'Christmas Day'],
            [$day(12, 31), "New Year's Eve"],
        ];
    }

    /**
     * Western Easter Sunday (the anonymous Gregorian algorithm), so the `calendar`
     * extension isn't needed for easter_date().
     */
    private function easter(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day);
    }
}
