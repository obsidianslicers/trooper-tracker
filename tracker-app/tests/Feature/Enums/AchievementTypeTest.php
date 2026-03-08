<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\AchievementType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AchievementTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_value_type_returns_number_for_metric_achievements(): void
    {
        $this->assertSame('number', AchievementType::TROOPER_RANK->valueType());
        $this->assertSame('number', AchievementType::TROOPER_SHIFTS->valueType());
        $this->assertSame('number', AchievementType::VOLUNTEER_HOURS->valueType());
    }

    public function test_value_type_returns_bool_for_milestone_achievements(): void
    {
        $this->assertSame('bool', AchievementType::FIRST_TROOP->valueType());
        $this->assertSame('bool', AchievementType::TROOPED_501->valueType());
    }

    public function test_is_metric_and_is_milestone_are_consistent_for_all_cases(): void
    {
        foreach (AchievementType::cases() as $case)
        {
            $this->assertSame(!$case->isMilestone(), $case->isMetric());
        }
    }

    public function test_to_icon_and_to_title_return_non_empty_strings(): void
    {
        foreach (AchievementType::cases() as $case)
        {
            $this->assertNotSame('', trim($case->toIcon()));
            $this->assertNotSame('', trim($case->toTitle()));
        }
    }

    public function test_to_title_uses_custom_text_for_milestone_case(): void
    {
        $this->assertSame('Combat Readiness Citation', AchievementType::FIRST_TROOP->toTitle());
    }
}
