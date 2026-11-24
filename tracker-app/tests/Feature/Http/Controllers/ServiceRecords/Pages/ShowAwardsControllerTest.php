<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords\Pages;

use App\Messages\Troopers\Queries\GetTrooperAwards;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ShowAwardsControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.awards'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_awards_with_default_days(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $award_troopers = collect([AwardTrooper::factory()->create()]);

        $messages = $this->fakeMessages([
            GetTrooperAwards::class => $award_troopers,
        ]);

        $response = $this->actingAs($trooper)->get(route('service-records.awards'));

        $messages->assertCalled(
            GetTrooperAwards::class,
            fn (array $params): bool => $params['lookback'] === 30
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('service-records/Awards')
            ->where('days', 30)
            ->has('awards', 1)
            ->where('awards.0.award', $award_troopers->first()->award->name)
            ->where('awards.0.trooper_name', $award_troopers->first()->trooper->display_name));
    }

    public function test_invoke_renders_awards_with_requested_days(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $messages = $this->fakeMessages([
            GetTrooperAwards::class => collect(),
        ]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.awards', ['days' => 120]));

        $messages->assertCalled(
            GetTrooperAwards::class,
            fn (array $params): bool => $params['lookback'] === 120
        );

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page->where('days', 120));
    }
}
