<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\AwardFrequency;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AwardFrequencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_normalize_date_monthly_returns_start_of_month(): void
    {
        $date = Carbon::parse('2026-03-08 13:22:55');

        $result = AwardFrequency::MONTHLY->normalizeDate($date);

        $this->assertSame('2026-03-01 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_normalize_date_quarterly_returns_first_of_quarter(): void
    {
        $date = Carbon::parse('2026-05-20 09:10:11');

        $result = AwardFrequency::QUARTERLY->normalizeDate($date);

        $this->assertSame('2026-04-01 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_normalize_date_annually_returns_start_of_year(): void
    {
        $date = Carbon::parse('2026-11-20 09:10:11');

        $result = AwardFrequency::ANNUALLY->normalizeDate($date);

        $this->assertSame('2026-01-01 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_normalize_date_once_and_random_keep_original_datetime(): void
    {
        $date = Carbon::parse('2026-03-08 13:22:55');

        $this->assertSame(
            '2026-03-08 13:22:55',
            AwardFrequency::ONCE->normalizeDate($date)->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-03-08 13:22:55',
            AwardFrequency::RANDOM->normalizeDate($date)->format('Y-m-d H:i:s')
        );
    }

    public function test_to_array_and_to_validator_cover_all_cases_sorted_by_name(): void
    {
        $cases = AwardFrequency::cases();
        usort($cases, fn($a, $b) => strcmp($a->name, $b->name));

        $expected_array = [];
        $expected_values = [];

        foreach ($cases as $case)
        {
            $expected_array[$case->value] = to_title($case->name)->toString();
            $expected_values[] = $case->value;
        }

        $actual_array = array_map(static fn($label): string => (string) $label, AwardFrequency::toArray());

        $this->assertSame($expected_array, $actual_array);
        $this->assertSame(implode(',', $expected_values), AwardFrequency::toValidator());
    }
}
