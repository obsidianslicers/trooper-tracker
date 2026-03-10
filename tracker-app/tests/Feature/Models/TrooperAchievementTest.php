<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\AchievementType;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperAchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_trooper_achievement(): void
    {
        $subject = TrooperAchievement::factory()->create();

        $this->assertInstanceOf(TrooperAchievement::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_get_display_order_attribute_returns_defined_order_for_known_achievement(): void
    {
        $subject = TrooperAchievement::factory()
            ->state([TrooperAchievement::TYPE => AchievementType::FIRST_TROOP])
            ->create();

        $result = $subject->display_order;

        $this->assertSame(1, $result);
    }

    public function test_get_display_order_attribute_returns_max_int_for_unknown_achievement(): void
    {
        $subject = TrooperAchievement::factory()
            ->state([TrooperAchievement::TYPE => AchievementType::TROOPER_RANK])
            ->create();

        $result = $subject->display_order;

        $this->assertLessThan(PHP_INT_MAX, $result);
    }

    public function test_get_display_order_attribute_for_trooper_rank(): void
    {
        $subject = TrooperAchievement::factory()
            ->state([TrooperAchievement::TYPE => AchievementType::TROOPER_RANK])
            ->create();

        $result = $subject->display_order;

        $this->assertSame(1, $result);
    }

    public function test_get_display_order_attribute_for_trooped_100(): void
    {
        $subject = TrooperAchievement::factory()
            ->state([TrooperAchievement::TYPE => AchievementType::TROOPED_100])
            ->create();

        $result = $subject->display_order;

        $this->assertSame(100, $result);
    }

    public function test_type_cast_works(): void
    {
        $subject = TrooperAchievement::factory()
            ->state([TrooperAchievement::TYPE => AchievementType::FIRST_TROOP])
            ->create();

        $this->assertInstanceOf(AchievementType::class, $subject->{TrooperAchievement::TYPE});
        $this->assertSame(AchievementType::FIRST_TROOP, $subject->{TrooperAchievement::TYPE});
    }
}