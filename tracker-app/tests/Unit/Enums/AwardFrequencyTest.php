<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AwardFrequency;
use Carbon\Carbon;
use Tests\TestCase;

class AwardFrequencyTest extends TestCase
{
    public function test_normalize_date_returns_copy_for_once(): void
    {
        $date = Carbon::parse('2026-02-15 14:30:00');

        $result = AwardFrequency::ONCE->normalizeDate($date);

        $this->assertEquals($date->toDateTimeString(), $result->toDateTimeString());
        $this->assertNotSame($date, $result);
    }

    public function test_normalize_date_returns_copy_for_random(): void
    {
        $date = Carbon::parse('2026-02-15 14:30:00');

        $result = AwardFrequency::RANDOM->normalizeDate($date);

        $this->assertEquals($date->toDateTimeString(), $result->toDateTimeString());
        $this->assertNotSame($date, $result);
    }

    public function test_normalize_date_returns_start_of_month_for_monthly(): void
    {
        $date = Carbon::parse('2026-02-15 14:30:00');

        $result = AwardFrequency::MONTHLY->normalizeDate($date);

        $this->assertTrue($result->isStartOfMonth());
        $this->assertEquals('2026-02-01', $result->toDateString());
    }

    public function test_normalize_date_returns_first_of_quarter_for_quarterly(): void
    {
        $date = Carbon::parse('2026-05-15 14:30:00');

        $result = AwardFrequency::QUARTERLY->normalizeDate($date);

        $this->assertEquals('2026-04-01', $result->toDateString());
    }

    public function test_normalize_date_returns_start_of_year_for_annually(): void
    {
        $date = Carbon::parse('2026-08-15 14:30:00');

        $result = AwardFrequency::ANNUALLY->normalizeDate($date);

        $this->assertTrue($result->isStartOfYear());
        $this->assertEquals('2026-01-01', $result->toDateString());
    }

    public function test_normalize_date_preserves_date_for_once_with_string(): void
    {
        $result = AwardFrequency::ONCE->normalizeDate('2026-02-15 14:30:00');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2026-02-15', $result->toDateString());
    }

    public function test_normalize_date_converts_string_to_carbon(): void
    {
        $result = AwardFrequency::MONTHLY->normalizeDate('2026-02-15');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertTrue($result->isStartOfMonth());
    }

    public function test_normalize_date_handles_multiple_quarters(): void
    {
        // Q1: Jan-Mar
        $this->assertEquals('2026-01-01', AwardFrequency::QUARTERLY->normalizeDate('2026-01-15')->toDateString());
        $this->assertEquals('2026-01-01', AwardFrequency::QUARTERLY->normalizeDate('2026-02-15')->toDateString());
        $this->assertEquals('2026-01-01', AwardFrequency::QUARTERLY->normalizeDate('2026-03-15')->toDateString());

        // Q2: Apr-Jun
        $this->assertEquals('2026-04-01', AwardFrequency::QUARTERLY->normalizeDate('2026-04-15')->toDateString());
        $this->assertEquals('2026-04-01', AwardFrequency::QUARTERLY->normalizeDate('2026-05-15')->toDateString());
        $this->assertEquals('2026-04-01', AwardFrequency::QUARTERLY->normalizeDate('2026-06-15')->toDateString());
    }

    public function test_normalize_date_does_not_mutate_original(): void
    {
        $original = Carbon::parse('2026-02-15 14:30:00');
        $original_string = $original->toDateTimeString();

        AwardFrequency::ANNUALLY->normalizeDate($original);

        $this->assertEquals($original_string, $original->toDateTimeString());
    }
}
