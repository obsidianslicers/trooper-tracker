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
}
