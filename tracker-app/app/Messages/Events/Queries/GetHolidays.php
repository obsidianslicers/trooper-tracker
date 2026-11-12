<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use Carbon\CarbonInterface;
use Hyperdrive\Message;
use Yasumi\Exception\ProviderNotFoundException;
use Yasumi\SubstituteHoliday;
use Yasumi\Yasumi;

/**
 * Query for the holidays shown on the events calendar, between two dates, from the
 * Yasumi library.
 *
 * `tracker.calendar.holidays` (`TRACKER_CALENDAR_HOLIDAYS`) names the Yasumi provider:
 * `USA` (the default), or another country or region such as `Canada` or
 * `UnitedKingdom/England`; `none` turns holidays off. Yasumi's "observed" weekday
 * copies of a weekend holiday are left out, so each holiday shows once, on its date.
 *
 * @method static array<string, list<string>> call(CarbonInterface $from, CarbonInterface $to)
 */
final class GetHolidays extends Message
{
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
        $provider = config('tracker.calendar.holidays');

        if (blank($provider) || $provider === 'none')
        {
            return [];
        }

        $from = $this->from->toDateString();
        $to = $this->to->toDateString();

        $holidays = [];

        try
        {
            for ($year = $this->from->year; $year <= $this->to->year; $year++)
            {
                foreach (Yasumi::create($provider, $year, 'en_US') as $holiday)
                {
                    $date = $holiday->format('Y-m-d');

                    if (!$holiday instanceof SubstituteHoliday && $date >= $from && $date <= $to)
                    {
                        $holidays[$date][] = $holiday->getName();
                    }
                }
            }
        }
        catch (ProviderNotFoundException $e)
        {
            //  a mistyped setting shouldn't take the calendar down
            report($e);

            return [];
        }

        ksort($holidays);

        return $holidays;
    }
}
