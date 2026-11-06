<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\DateConcerns;
use Carbon\Carbon;
use Tests\TestCase;

class DateConcernsTest extends TestCase
{
    public function test_earliest_date_time_prefers_the_earlier_non_null_value(): void
    {
        $early = Carbon::parse('2026-01-01');
        $late = Carbon::parse('2026-06-01');

        $this->assertSame($early, $this->subject()->earliest($late, $early));
        $this->assertSame($early, $this->subject()->earliest($early, $late));
        $this->assertSame($late, $this->subject()->earliest(null, $late));
        $this->assertSame($early, $this->subject()->earliest($early, null));
        $this->assertNull($this->subject()->earliest(null, null));
    }

    public function test_latest_date_time_prefers_the_later_non_null_value(): void
    {
        $early = Carbon::parse('2026-01-01');
        $late = Carbon::parse('2026-06-01');

        $this->assertSame($late, $this->subject()->latest($early, $late));
        $this->assertSame($late, $this->subject()->latest($late, $early));
        $this->assertSame($early, $this->subject()->latest(null, $early));
        $this->assertSame($late, $this->subject()->latest($late, null));
    }

    private function subject(): object
    {
        return new class {
            use DateConcerns;

            public function earliest(mixed $target, mixed $source): mixed
            {
                return $this->earliestDateTime($target, $source);
            }

            public function latest(mixed $target, mixed $source): mixed
            {
                return $this->latestDateTime($target, $source);
            }
        };
    }
}
