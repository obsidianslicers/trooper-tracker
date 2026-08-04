<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\NoticeType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NoticeTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_description_returns_non_empty_message_for_each_case(): void
    {
        foreach (NoticeType::cases() as $case)
        {
            $this->assertNotSame('', trim($case->description()));
        }
    }

    public function test_to_descriptions_contains_all_enum_keys(): void
    {
        $descriptions = NoticeType::toDescriptions();

        foreach (NoticeType::cases() as $case)
        {
            $this->assertArrayHasKey($case->value, $descriptions);
        }
    }

    public function test_to_options_contains_all_enum_keys_with_prefix_icons(): void
    {
        $options = NoticeType::toOptions();

        foreach (NoticeType::cases() as $case)
        {
            $this->assertArrayHasKey($case->value, $options);
            $this->assertTrue(strlen($options[$case->value]) > strlen(NoticeType::from($case->value)->description()));
        }
    }

    public function test_to_array_and_to_validator_cover_all_cases_sorted_by_name(): void
    {
        $cases = NoticeType::cases();
        usort($cases, fn($a, $b) => strcmp($a->name, $b->name));

        $expected_array = [];
        $expected_values = [];

        foreach ($cases as $case)
        {
            $expected_array[$case->value] = to_title($case->name)->toString();
            $expected_values[] = $case->value;
        }

        $actual_array = array_map(static fn($label): string => (string) $label, NoticeType::toArray());

        $this->assertSame($expected_array, $actual_array);
        $this->assertSame('in:' . implode(',', $expected_values), NoticeType::toValidator());
    }
}
