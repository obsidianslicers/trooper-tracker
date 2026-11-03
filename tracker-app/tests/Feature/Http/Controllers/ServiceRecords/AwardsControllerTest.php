<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Messages\Troopers\Queries\GetTrooperAwards;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class AwardsControllerTest extends TestCase
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

        $award_troopers = collect([AwardTrooper::factory()->make()]);

        $messages = $this->fakeMessages([
            GetTrooperAwards::class => $award_troopers,
        ]);

        $response = $this->actingAs($trooper)->get(route('service-records.awards'));

        $messages->assertCalled(
            GetTrooperAwards::class,
            fn (array $params): bool => $params['lookback'] === 30
        );

        $response->assertOk();
        $response->assertViewIs('pages.service-records.awards');
        $response->assertViewHas('days', 30);
        $response->assertViewHas('award_troopers', function (Collection $result): bool
        {
            return $result->count() === 1;
        });
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
        $response->assertViewHas('days', 120);
    }
}
