<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AchievementType;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\TrooperAchievement
 */
class TrooperAchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_type_to_achievement_type_enum(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $achievement = TrooperAchievement::create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => '1',
        ]);

        // Act
        $refreshed_achievement = $achievement->fresh();

        // Assert
        $this->assertInstanceOf(AchievementType::class, $refreshed_achievement->type);
        $this->assertSame(AchievementType::FIRST_TROOP, $refreshed_achievement->type);
    }

    public function test_casts_boolean_value_correctly(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $achievement = TrooperAchievement::create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPED_10->value,
            TrooperAchievement::VALUE => '1',
        ]);

        // Act
        $refreshed_achievement = $achievement->fresh();

        // Assert
        $this->assertIsBool($refreshed_achievement->value);
        $this->assertTrue($refreshed_achievement->value);
    }

    public function test_casts_number_value_correctly(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $achievement = TrooperAchievement::create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => '42',
        ]);

        // Act
        $refreshed_achievement = $achievement->fresh();

        // Assert
        $this->assertIsInt($refreshed_achievement->value);
        $this->assertSame(42, $refreshed_achievement->value);
    }

    public function test_belongs_to_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $achievement = TrooperAchievement::create([
            'trooper_id' => $trooper->id,
            'type' => AchievementType::FIRST_TROOP->value,
            'value' => '1',
        ]);

        // Act
        $related_trooper = $achievement->trooper;

        // Assert
        $this->assertInstanceOf(Trooper::class, $related_trooper);
        $this->assertEquals($trooper->id, $related_trooper->id);
    }

    public function test_display_order_attribute_returns_predefined_order(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $achievement = TrooperAchievement::create([
            'trooper_id' => $trooper->id,
            'type' => AchievementType::FIRST_TROOP->value,
            'value' => '1',
        ]);

        // Act
        $display_order = $achievement->display_order;

        // Assert
        $this->assertSame(3, $display_order);
    }

    public function test_display_order_attribute_returns_max_for_unknown_achievement(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $achievement = TrooperAchievement::create([
            'trooper_id' => $trooper->id,
            'type' => AchievementType::VOLUNTEER_HOURS->value,
            'value' => '100',
        ]);

        // Act
        $display_order = $achievement->display_order;

        // Assert
        $this->assertSame(PHP_INT_MAX, $display_order);
    }

    public function test_display_order_constant_includes_all_milestone_achievements(): void
    {
        // Assert - Verify all expected milestone achievements are in the constant
        $expected_achievements = [
            AchievementType::TROOPED_ALL_SQUADS->value,
            AchievementType::TROOPER_SHIFTS->value,
            AchievementType::FIRST_TROOP->value,
            AchievementType::TROOPED_10->value,
            AchievementType::TROOPED_25->value,
            AchievementType::TROOPED_50->value,
            AchievementType::TROOPED_75->value,
            AchievementType::TROOPED_100->value,
            AchievementType::TROOPED_150->value,
            AchievementType::TROOPED_200->value,
            AchievementType::TROOPED_250->value,
            AchievementType::TROOPED_300->value,
            AchievementType::TROOPED_400->value,
            AchievementType::TROOPED_500->value,
            AchievementType::TROOPED_501->value,
        ];

        foreach ($expected_achievements as $achievement_type)
        {
            $this->assertArrayHasKey(
                $achievement_type,
                TrooperAchievement::DISPLAY_ORDER,
                "Expected {$achievement_type} to be in DISPLAY_ORDER constant"
            );
        }
    }

    public function test_all_milestone_achievements_have_icons(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $milestone_types = [
            AchievementType::TROOPED_ALL_SQUADS,
            AchievementType::TROOPER_SHIFTS,
            AchievementType::FIRST_TROOP,
            AchievementType::TROOPED_10,
            AchievementType::TROOPED_25,
            AchievementType::TROOPED_50,
            AchievementType::TROOPED_75,
            AchievementType::TROOPED_100,
            AchievementType::TROOPED_150,
            AchievementType::TROOPED_200,
            AchievementType::TROOPED_250,
            AchievementType::TROOPED_300,
            AchievementType::TROOPED_400,
            AchievementType::TROOPED_500,
            AchievementType::TROOPED_501,
        ];

        foreach ($milestone_types as $type)
        {
            // Arrange
            $achievement = TrooperAchievement::create([
                'trooper_id' => $trooper->id,
                'type' => $type->value,
                'value' => '1',
            ]);

            // Act
            $icon = $achievement->icon;

            // Assert
            $this->assertNotEmpty($icon, "Expected icon for {$type->value}");
            $this->assertNotSame('fa-circle-question', $icon, "Expected specific icon for {$type->value}, got default");
        }
    }

    public function test_all_milestone_achievements_have_titles(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $milestone_types = [
            AchievementType::TROOPED_ALL_SQUADS,
            AchievementType::TROOPER_SHIFTS,
            AchievementType::FIRST_TROOP,
            AchievementType::TROOPED_10,
            AchievementType::TROOPED_25,
            AchievementType::TROOPED_50,
            AchievementType::TROOPED_75,
            AchievementType::TROOPED_100,
            AchievementType::TROOPED_150,
            AchievementType::TROOPED_200,
            AchievementType::TROOPED_250,
            AchievementType::TROOPED_300,
            AchievementType::TROOPED_400,
            AchievementType::TROOPED_500,
            AchievementType::TROOPED_501,
        ];

        foreach ($milestone_types as $type)
        {
            // Arrange
            $achievement = TrooperAchievement::create([
                'trooper_id' => $trooper->id,
                'type' => $type->value,
                'value' => '1',
            ]);

            // Act
            $title = $achievement->title;

            // Assert
            $this->assertNotEmpty($title, "Expected title for {$type->value}");
            $this->assertNotSame('Unknown Achievement', $title, "Expected specific title for {$type->value}, got default");
        }
    }
}
