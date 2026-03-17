<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Bus\MagicBus;
use App\Features\Troopers\Queries\GetTrooperAchievementsQuery;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class AchievementsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.achievements'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_achievements_with_default_days(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $achievement_trooper = Trooper::factory()->asMember()->create();

        $trooper_achievements = collect([
            TrooperAchievement::factory()
                ->forTrooper($achievement_trooper)
                ->earnedOn(now())
                ->create(),
        ]);

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($trooper_achievements): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperAchievementsQuery $query): bool
                {
                    return $query->lookback === 30;
                })
                ->andReturn($trooper_achievements);
        });

        $response = $this->actingAs($trooper)->get(route('service-records.achievements'));

        $response->assertOk();
        $response->assertViewIs('pages.service-records.achievements');
        $response->assertViewHas('days', 30);
        $response->assertViewHas('trooper_achievements', function (Collection $result): bool
        {
            return $result->count() === 1;
        });
    }

    public function test_invoke_renders_achievements_with_requested_days(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $this->mock(MagicBus::class, function (MockInterface $mock): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperAchievementsQuery $query): bool
                {
                    return $query->lookback === 60;
                })
                ->andReturn(collect());
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.achievements', ['days' => 60]));

        $response->assertOk();
        $response->assertViewHas('days', 60);
    }
}
