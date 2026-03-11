<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Enums\AchievementType;
use App\Features\Troopers\Queries\GetTrooperAchievementsQuery;
use App\Features\Troopers\Queries\GetTrooperAchievementsQueryHandler;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperAchievementsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_only_milestones_within_lookback(): void
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

        $subject = new GetTrooperAchievementsQueryHandler();

        $result = $subject(new GetTrooperAchievementsQuery(30));

        $this->assertCount(1, $result);
        $this->assertSame($included->id, $result->first()->id);
        $this->assertTrue($result->first()->relationLoaded('trooper'));
        $this->assertSame(AchievementType::FIRST_TROOP, $result->first()->type);
    }

    public function test_invoke_orders_milestones_by_achievement_date_descending(): void
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

        $subject = new GetTrooperAchievementsQueryHandler();

        $result = $subject(new GetTrooperAchievementsQuery(30));

        $this->assertGreaterThanOrEqual(
            $result->last()->achievement_date->timestamp,
            $result->first()->achievement_date->timestamp,
        );
    }
}
