<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\NotificationFrequency;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationFrequencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_to_array_and_to_validator_cover_all_cases_sorted_by_name(): void
    {
        $cases = NotificationFrequency::cases();
        usort($cases, fn($a, $b) => strcmp($a->name, $b->name));

        $expected_array = [];
        $expected_values = [];

        foreach ($cases as $case)
        {
            $expected_array[$case->value] = to_title($case->name)->toString();
            $expected_values[] = $case->value;
        }

        $actual_array = array_map(static fn($label): string => (string) $label, NotificationFrequency::toArray());

        $this->assertSame($expected_array, $actual_array);
        $this->assertSame(implode(',', $expected_values), NotificationFrequency::toValidator());
    }
}
