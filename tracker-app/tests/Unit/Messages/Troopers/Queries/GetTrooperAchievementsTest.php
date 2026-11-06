<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Enums\AchievementType;
use App\Messages\Troopers\Queries\GetTrooperAchievements;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperAchievementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_milestones_within_lookback(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $included = TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::FIRST_TROOP)
            ->earnedOn(Carbon::now()->subDays(2))
            ->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::TROOPER_RANK)
            ->earnedOn(Carbon::now()->subDays(2))
            ->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::TROOPED_10)
            ->earnedOn(Carbon::now()->subDays(120))
            ->create();

        $result = (new GetTrooperAchievements(30))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($included->id, $result->first()->id);
        $this->assertTrue($result->first()->relationLoaded('trooper'));
        $this->assertSame(AchievementType::FIRST_TROOP, $result->first()->type);
    }

    public function test_handle_loads_organization_for_club_scoped_milestones(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withName('501st Legion')->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withType(AchievementType::FIRST_TROOP)
            ->earnedOn(Carbon::now()->subDays(2))
            ->create([TrooperAchievement::VALUE => true]);

        $result = (new GetTrooperAchievements(30))->handle();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->relationLoaded('organization'));
        $this->assertSame(
            '501st Legion: First Troop - Combat Readiness Citation',
            $result->first()->display_description,
        );
    }

    public function test_handle_orders_milestones_by_achievement_date_descending(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::TROOPED_25)
            ->earnedOn(Carbon::now()->subDays(9))
            ->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::TROOPED_50)
            ->earnedOn(Carbon::now()->subDays(3))
            ->create();

        $result = (new GetTrooperAchievements(30))->handle();

        $this->assertGreaterThanOrEqual(
            $result->last()->achievement_date->timestamp,
            $result->first()->achievement_date->timestamp,
        );
    }
}
