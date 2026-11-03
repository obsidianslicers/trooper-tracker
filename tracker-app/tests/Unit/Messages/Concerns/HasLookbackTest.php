<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Concerns;

use App\Messages\Concerns\HasLookback;
use Carbon\Carbon;
use Tests\TestCase;

class HasLookbackTest extends TestCase
{
    public function test_parse_lookback_returns_null_when_lookback_is_null(): void
    {
        $subject = $this->makeSubject(null);

        $this->assertNull($subject->parseLookback());
    }

    public function test_parse_lookback_converts_days_to_start_of_day(): void
    {
        $subject = $this->makeSubject(7);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals(now()->startOfDay()->subDays(7), $result);
    }

    public function test_parse_lookback_parses_date_strings(): void
    {
        $subject = $this->makeSubject('2026-01-15');

        $this->assertSame('2026-01-15', $subject->parseLookback()->toDateString());
    }

    public function test_parse_lookback_returns_carbon_instances_unchanged(): void
    {
        $date = Carbon::parse('2026-02-01');

        $subject = $this->makeSubject($date);

        $this->assertSame($date, $subject->parseLookback());
    }

    private function makeSubject(int|string|Carbon|null $lookback): object
    {
        return new class($lookback) {
            use HasLookback;

            public function __construct(public readonly int|string|Carbon|null $lookback) {}
        };
    }
}
