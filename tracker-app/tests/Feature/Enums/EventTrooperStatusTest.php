<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\EventTrooperStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventTrooperStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_to_sign_up_array_respects_tentative_flag(): void
    {
        $without_tentative = EventTrooperStatus::toSignUpArray(false);
        $with_tentative = EventTrooperStatus::toSignUpArray(true);

        $this->assertArrayNotHasKey(EventTrooperStatus::TENTATIVE->value, $without_tentative);
        $this->assertArrayHasKey(EventTrooperStatus::TENTATIVE->value, $with_tentative);
    }

    public function test_intent_to_go_array_includes_going_and_tentative_only(): void
    {
        $result = EventTrooperStatus::intentToGoArray();

        $this->assertSame([
            EventTrooperStatus::GOING,
            EventTrooperStatus::TENTATIVE,
        ], $result);
    }

    public function test_intends_to_go_returns_expected_values_for_each_case(): void
    {
        $this->assertTrue(EventTrooperStatus::GOING->intendsToGo());
        $this->assertTrue(EventTrooperStatus::TENTATIVE->intendsToGo());

        $this->assertFalse(EventTrooperStatus::NONE->intendsToGo());
        $this->assertFalse(EventTrooperStatus::STAND_BY->intendsToGo());
        $this->assertFalse(EventTrooperStatus::ATTENDED->intendsToGo());
        $this->assertFalse(EventTrooperStatus::CANCELLED->intendsToGo());
        $this->assertFalse(EventTrooperStatus::PENDING->intendsToGo());
        $this->assertFalse(EventTrooperStatus::NOT_PICKED->intendsToGo());
        $this->assertFalse(EventTrooperStatus::NO_SHOW->intendsToGo());
        $this->assertFalse(EventTrooperStatus::UNABLE_TO_ATTEND->intendsToGo());
    }

    public function test_icon_and_color_return_non_empty_values_for_each_case(): void
    {
        foreach (EventTrooperStatus::cases() as $case)
        {
            $this->assertNotSame('', trim($case->icon()));
            $this->assertNotSame('', trim($case->color()));
        }
    }

    public function test_icon_tag_contains_icon_and_color_classes(): void
    {
        $subject = EventTrooperStatus::GOING;

        $tag = $subject->iconTag();

        $this->assertStringContainsString($subject->icon(), $tag);
        $this->assertStringContainsString($subject->color(), $tag);
        $this->assertStringStartsWith('<i class="fa fa-fw ', $tag);
    }

    public function test_to_array_and_to_validator_cover_all_cases_sorted_by_name(): void
    {
        $cases = EventTrooperStatus::cases();
        usort($cases, fn($a, $b) => strcmp($a->name, $b->name));

        $expected_array = [];
        $expected_values = [];

        foreach ($cases as $case)
        {
            $expected_array[$case->value] = to_title($case->name)->toString();
            $expected_values[] = $case->value;
        }

        $actual_array = array_map(static fn($label): string => (string) $label, EventTrooperStatus::toArray());

        $this->assertSame($expected_array, $actual_array);
        $this->assertSame(implode(',', $expected_values), EventTrooperStatus::toValidator());
    }
}
