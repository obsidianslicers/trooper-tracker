<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Messages\Events\Queries\GetHolidays;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GetHolidaysTest extends TestCase
{
    public function test_handle_returns_the_us_holidays_by_date_without_observed_days(): void
    {
        //  July 4, 2026 is a Saturday: Yasumi also has "Independence Day observed" on the 3rd
        $holidays = GetHolidays::call(from: Carbon::parse('2026-07-01'), to: Carbon::parse('2026-11-30'));

        $this->assertSame([
            '2026-07-04' => ['Independence Day'],
            '2026-09-07' => ['Labor Day'],
            '2026-10-12' => ['Columbus Day'],
            '2026-11-11' => ['Veterans Day'],
            '2026-11-26' => ['Thanksgiving Day'],
        ], $holidays);
    }

    public function test_handle_keeps_only_days_in_range_across_a_new_year(): void
    {
        $holidays = GetHolidays::call(from: Carbon::parse('2026-12-26'), to: Carbon::parse('2027-01-18'));

        $this->assertSame([
            '2027-01-01' => ['New Year’s Day'],
            '2027-01-18' => ['Dr. Martin Luther King Jr’s Birthday'],
        ], $holidays);
    }

    public function test_handle_uses_the_configured_provider(): void
    {
        config(['tracker.calendar.holidays' => 'Canada']);

        $holidays = GetHolidays::call(from: Carbon::parse('2026-07-01'), to: Carbon::parse('2026-07-01'));

        $this->assertSame(['2026-07-01' => ['Canada Day']], $holidays);
    }

    public function test_handle_returns_nothing_when_turned_off(): void
    {
        config(['tracker.calendar.holidays' => 'none']);

        $holidays = GetHolidays::call(from: Carbon::parse('2026-01-01'), to: Carbon::parse('2026-12-31'));

        $this->assertSame([], $holidays);
    }

    public function test_handle_returns_nothing_for_an_unknown_provider(): void
    {
        config(['tracker.calendar.holidays' => 'Atlantis']);

        Log::spy();

        $holidays = GetHolidays::call(from: Carbon::parse('2026-01-01'), to: Carbon::parse('2026-12-31'));

        $this->assertSame([], $holidays);
        Log::shouldHaveReceived('error')->once();
    }
}
