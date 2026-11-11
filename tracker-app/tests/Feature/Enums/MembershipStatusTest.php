<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\MembershipStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MembershipStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_to_array_and_to_validator_cover_all_cases_sorted_by_name(): void
    {
        $cases = MembershipStatus::cases();
        usort($cases, fn ($a, $b) => strcmp($a->name, $b->name));

        $expected_array = [];
        $expected_values = [];

        foreach ($cases as $case)
        {
            $expected_array[$case->value] = to_title($case->name)->toString();
            $expected_values[] = $case->value;
        }

        $actual_array = array_map(static fn ($label): string => (string) $label, MembershipStatus::toArray());

        $this->assertSame($expected_array, $actual_array);
        $this->assertSame('in:'.implode(',', $expected_values), MembershipStatus::toValidator());
    }

    public function test_choices_for_a_moderator_leave_out_lock_out_statuses_except_the_current_one(): void
    {
        $choices = array_keys(MembershipStatus::choicesFor(false, MembershipStatus::DEPARTED));

        $this->assertContains(MembershipStatus::ACTIVE->value, $choices);
        $this->assertContains(MembershipStatus::DEPARTED->value, $choices);
        $this->assertNotContains(MembershipStatus::INVALID->value, $choices);
        $this->assertNotContains(MembershipStatus::MERGED->value, $choices);
    }

    public function test_choices_for_an_administrator_include_every_status(): void
    {
        $this->assertSame(
            array_keys(MembershipStatus::toArray()),
            array_keys(MembershipStatus::choicesFor(true))
        );
    }
}
