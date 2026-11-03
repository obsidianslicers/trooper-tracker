<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Messages\Troopers\Queries\GetTrooperAchievements;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class AchievementsControllerTest extends TestCase
{
    use FakesMessages;
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

        $messages = $this->fakeMessages([
            GetTrooperAchievements::class => $trooper_achievements,
        ]);

        $response = $this->actingAs($trooper)->get(route('service-records.achievements'));

        $messages->assertCalled(
            GetTrooperAchievements::class,
            fn (array $params): bool => $params['lookback'] === 30
        );

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

        $messages = $this->fakeMessages([
            GetTrooperAchievements::class => collect(),
        ]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.achievements', ['days' => 60]));

        $messages->assertCalled(
            GetTrooperAchievements::class,
            fn (array $params): bool => $params['lookback'] === 60
        );

        $response->assertOk();
        $response->assertViewHas('days', 60);
    }
}
