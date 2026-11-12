<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Messages\Events\Queries\GetHolidays;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GetHolidaysTest extends TestCase
{
    public function test_handle_returns_the_us_holidays_of_a_year_in_date_order(): void
    {
        $holidays = GetHolidays::call(from: Carbon::parse('2026-01-01'), to: Carbon::parse('2026-12-31'));

        $this->assertSame([
            '2026-01-01' => ["New Year's Day"],
            '2026-01-19' => ['Martin Luther King Jr. Day'],
            '2026-02-14' => ["Valentine's Day"],
            '2026-02-16' => ["Presidents' Day"],
            '2026-03-17' => ["St. Patrick's Day"],
            '2026-04-05' => ['Easter'],
            '2026-05-10' => ["Mother's Day"],
            '2026-05-25' => ['Memorial Day'],
            '2026-06-19' => ['Juneteenth'],
            '2026-06-21' => ["Father's Day"],
            '2026-07-04' => ['Independence Day'],
            '2026-09-07' => ['Labor Day'],
            '2026-10-12' => ['Columbus Day'],
            '2026-10-31' => ['Halloween'],
            '2026-11-11' => ['Veterans Day'],
            '2026-11-26' => ['Thanksgiving'],
            '2026-12-24' => ['Christmas Eve'],
            '2026-12-25' => ['Christmas Day'],
            '2026-12-31' => ["New Year's Eve"],
        ], $holidays);
    }

    public function test_handle_keeps_only_days_in_range_across_a_new_year(): void
    {
        $holidays = GetHolidays::call(from: Carbon::parse('2026-12-27'), to: Carbon::parse('2027-01-18'));

        $this->assertSame([
            '2026-12-31' => ["New Year's Eve"],
            '2027-01-01' => ["New Year's Day"],
            '2027-01-18' => ['Martin Luther King Jr. Day'],
        ], $holidays);
    }

    public function test_handle_lists_both_holidays_on_a_shared_day(): void
    {
        //  Father's Day fell on Juneteenth in 2022
        $holidays = GetHolidays::call(from: Carbon::parse('2022-06-19'), to: Carbon::parse('2022-06-19'));

        $this->assertSame(['2022-06-19' => ["Father's Day", 'Juneteenth']], $holidays);
    }

    public static function easterDates(): array
    {
        return [
            ['2008-03-23'],
            ['2024-03-31'],
            ['2025-04-20'],
            ['2027-03-28'],
            ['2028-04-16'],
            ['2038-04-25'],
        ];
    }

    #[DataProvider('easterDates')]
    public function test_handle_finds_easter(string $date): void
    {
        $year = substr($date, 0, 4);

        $holidays = GetHolidays::call(from: Carbon::parse("{$year}-03-01"), to: Carbon::parse("{$year}-04-30"));

        $this->assertSame(['Easter'], $holidays[$date] ?? null);
    }

    public function test_handle_returns_nothing_when_turned_off(): void
    {
        config(['tracker.calendar.holidays' => 'none']);

        $holidays = GetHolidays::call(from: Carbon::parse('2026-01-01'), to: Carbon::parse('2026-12-31'));

        $this->assertSame([], $holidays);
    }
}
