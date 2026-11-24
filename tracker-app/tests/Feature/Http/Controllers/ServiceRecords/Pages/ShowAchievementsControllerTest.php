<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords\Pages;

use App\Messages\Troopers\Queries\GetTrooperAchievements;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ShowAchievementsControllerTest extends TestCase
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
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('service-records/Achievements')
            ->where('days', 30)
            ->has('achievements', 1)
            ->where('achievements.0.trooper_name', $achievement_trooper->display_name)
            ->where('achievements.0.trooper_url', route('service-records.trooper', ['trooper' => $achievement_trooper])));
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
        $response->assertInertia(fn (Assert $page): Assert => $page->where('days', 60));
    }
}
