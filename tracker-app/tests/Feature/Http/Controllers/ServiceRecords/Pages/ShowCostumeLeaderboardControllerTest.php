<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords\Pages;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\Queries\GetCostumeTrooperLeaderboard;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ShowCostumeLeaderboardControllerTest extends TestCase
{
    use FakesMessages;
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $costume = Costume::factory()->create();

        $response = $this->get(route('service-records.costume', $costume));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_full_view_for_regular_request(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([GetCostumeTrooperLeaderboard::class => $this->handlerResult()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $messages->assertCalled(GetCostumeTrooperLeaderboard::class);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('service-records/Costume'));
    }

    public function test_invoke_resolves_days_from_query_string(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([GetCostumeTrooperLeaderboard::class => $this->handlerResult()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', ['costume' => $costume, 'days' => 90]));

        $messages->assertCalled(GetCostumeTrooperLeaderboard::class, fn (array $params): bool => $params['costume']->id === $costume->id && $params['lookback'] === 90 && $params['organization'] === null && $params['limit'] === 30);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('filters.days', 90));
    }

    public function test_invoke_defaults_to_all_time_when_days_absent(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([GetCostumeTrooperLeaderboard::class => $this->handlerResult()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $messages->assertCalled(GetCostumeTrooperLeaderboard::class, fn (array $params): bool => $params['lookback'] === null);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('filters.days', null));
    }

    public function test_invoke_ignores_invalid_days_value(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([GetCostumeTrooperLeaderboard::class => $this->handlerResult()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', ['costume' => $costume, 'days' => 999]));

        $messages->assertCalled(GetCostumeTrooperLeaderboard::class, fn (array $params): bool => $params['lookback'] === null);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('filters.days', null));
    }

    public function test_invoke_resolves_organization_from_query_string(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $messages = $this->fakeMessages([GetCostumeTrooperLeaderboard::class => $this->handlerResult()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', [
                'costume' => $costume,
                'organization_id' => $organization->id,
            ]));

        $messages->assertCalled(GetCostumeTrooperLeaderboard::class, fn (array $params): bool => $params['organization']?->id === $organization->id);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('filters.organization_id', $organization->id));
    }

    public function test_invoke_passes_costume_to_view(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([GetCostumeTrooperLeaderboard::class => $this->handlerResult()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $messages->assertCalled(GetCostumeTrooperLeaderboard::class);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('costume.id', $costume->id)
            ->where('costume.name', $costume->name));
    }

    public function test_invoke_passes_stats_and_top_troopers_to_view(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([GetCostumeTrooperLeaderboard::class => $this->handlerResult()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $messages->assertCalled(GetCostumeTrooperLeaderboard::class);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('costume.top_troopers', [])
            ->where('costume.total_deployments', '0')
            ->where('costume.unique_troopers', '0')
            ->where('costume.last_deployed', null));
    }

    /**
     * @return array{top_troopers: Collection, stats: array{total_deployments: int, unique_troopers: int, last_deployed_at: null}}
     */
    private function handlerResult(): array
    {
        return [
            'top_troopers' => collect(),
            'stats' => [
                'total_deployments' => 0,
                'unique_troopers' => 0,
                'last_deployed_at' => null,
            ],
        ];
    }

    public function test_invoke_shows_each_troopers_real_name_next_to_their_username(): void
    {
        $viewer = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $named = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'TK-Zeta', Trooper::LEGAL_NAME => 'Jane Example']);
        $costume = Costume::factory()->create();
        $troop = $this->troopToConfirm($named, EventTrooperStatus::ATTENDED);
        $troop->forceFill([EventTrooper::COSTUME_ID => $costume->id])->saveQuietly();

        $response = $this->actingAs($viewer)->get(route('service-records.costume', $costume));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('costume.top_troopers.0.display_name', 'TK-Zeta')
            ->where('costume.top_troopers.0.legal_name', 'Jane Example'));
    }
}
