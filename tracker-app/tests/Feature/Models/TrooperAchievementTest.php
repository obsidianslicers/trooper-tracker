<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\AchievementType;
use App\Models\Organization;
use App\Models\Trooper;
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

    public function test_global_and_club_scoped_achievements_can_share_type_for_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->asOrganization()->withName('501st Legion')->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([TrooperAchievement::VALUE => true]);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([TrooperAchievement::VALUE => true]);

        $this->assertDatabaseCount('tt_trooper_achievements', 2);
    }

    public function test_display_description_includes_club_name_for_scoped_achievement(): void
    {
        $organization = Organization::factory()->asOrganization()->withName('Rebel Legion')->create();
        $subject = TrooperAchievement::factory()
            ->forOrganization($organization)
            ->withType(AchievementType::TROOPED_10)
            ->create([TrooperAchievement::VALUE => true]);

        $this->assertSame(
            'Rebel Legion: 10 Troops - Frontier Service Ribbon',
            $subject->display_description,
        );
        $this->assertTrue($subject->isClubScoped());
    }
}
