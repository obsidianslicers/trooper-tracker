<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AchievementType;
use Tests\TestCase;

class AchievementTypeTest extends TestCase
{
    public function test_value_type_returns_number_for_metrics(): void
    {
        $this->assertEquals('number', AchievementType::TROOPER_RANK->valueType());
        $this->assertEquals('number', AchievementType::TROOPER_SHIFTS->valueType());
        $this->assertEquals('number', AchievementType::VOLUNTEER_HOURS->valueType());
        $this->assertEquals('number', AchievementType::DIRECT_FUNDS->valueType());
        $this->assertEquals('number', AchievementType::INDIRECT_FUNDS->valueType());
    }

    public function test_value_type_returns_bool_for_milestones(): void
    {
        $this->assertEquals('bool', AchievementType::FIRST_TROOP->valueType());
        $this->assertEquals('bool', AchievementType::TROOPED_10->valueType());
        $this->assertEquals('bool', AchievementType::TROOPED_100->valueType());
    }

    public function test_to_icon_returns_valid_font_awesome_class(): void
    {
        $this->assertStringStartsWith('fa-', AchievementType::FIRST_TROOP->toIcon());
        $this->assertStringStartsWith('fa-', AchievementType::TROOPED_501->toIcon());
    }

    public function test_to_icon_returns_special_class_for_501_milestone(): void
    {
        $this->assertEquals('fa-brands fa-empire', AchievementType::TROOPED_501->toIcon());
    }

    public function test_to_icon_returns_question_mark_for_unknown(): void
    {
        $this->assertEquals('fa-circle-question', AchievementType::TROOPER_RANK->toIcon());
    }

    public function test_to_title_returns_string_with_troop_count(): void
    {
        $this->assertStringContainsString('1 Troop', AchievementType::FIRST_TROOP->toTitle());
        $this->assertStringContainsString('10 Troops', AchievementType::TROOPED_10->toTitle());
        $this->assertStringContainsString('100 Troops', AchievementType::TROOPED_100->toTitle());
    }

    public function test_to_title_returns_star_wars_themed_names(): void
    {
        $this->assertStringContainsString('Mission Initiated', AchievementType::FIRST_TROOP->toTitle());
        $this->assertStringContainsString('Centurion Crest', AchievementType::TROOPED_100->toTitle());
        $this->assertStringContainsString('Vader\'s Fist', AchievementType::TROOPED_501->toTitle());
    }

    public function test_to_title_includes_sector_sweep_for_all_squads(): void
    {
        $this->assertStringContainsString('Sector Sweep', AchievementType::TROOPED_ALL_SQUADS->toTitle());
    }

    public function test_is_metric_returns_true_for_metrics(): void
    {
        $this->assertTrue(AchievementType::TROOPER_RANK->isMetric());
        $this->assertTrue(AchievementType::TROOPER_SHIFTS->isMetric());
        $this->assertTrue(AchievementType::VOLUNTEER_HOURS->isMetric());
        $this->assertTrue(AchievementType::DIRECT_FUNDS->isMetric());
        $this->assertTrue(AchievementType::INDIRECT_FUNDS->isMetric());
    }

    public function test_is_metric_returns_false_for_milestones(): void
    {
        $this->assertFalse(AchievementType::FIRST_TROOP->isMetric());
        $this->assertFalse(AchievementType::TROOPED_10->isMetric());
        $this->assertFalse(AchievementType::TROOPED_100->isMetric());
    }

    public function test_is_milestone_returns_true_for_milestones(): void
    {
        $this->assertTrue(AchievementType::FIRST_TROOP->isMilestone());
        $this->assertTrue(AchievementType::TROOPED_10->isMilestone());
        $this->assertTrue(AchievementType::TROOPED_100->isMilestone());
        $this->assertTrue(AchievementType::TROOPED_501->isMilestone());
    }

    public function test_is_milestone_returns_false_for_metrics(): void
    {
        $this->assertFalse(AchievementType::TROOPER_RANK->isMilestone());
        $this->assertFalse(AchievementType::TROOPER_SHIFTS->isMilestone());
        $this->assertFalse(AchievementType::VOLUNTEER_HOURS->isMilestone());
    }

    public function test_metric_and_milestone_are_mutually_exclusive(): void
    {
        foreach (AchievementType::cases() as $case)
        {
            $this->assertNotEquals(
                $case->isMetric(),
                $case->isMilestone(),
                sprintf('%s should be either metric or milestone, not both', $case->name)
            );
        }
    }
}
