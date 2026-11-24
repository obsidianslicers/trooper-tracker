<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords\Pages;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\Queries\GetCostumeTrooperLeaderboard;
use App\Messages\Reports\Queries\GetLeaderboardMetrics;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ShowLeaderboardControllerTest extends TestCase
{
    use FakesMessages;
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.leaderboard'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_leaderboard_with_requested_days(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $this->emptyMetrics()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard', ['days' => 90]));

        $messages->assertCalled(GetLeaderboardMetrics::class, fn (array $params): bool => $params['lookback'] === 90
            && $params['organization'] === null
            && $params['limit'] === 30);
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('service-records/Leaderboard')
            ->where('filters.days', 90)
            ->where('filters.day_options', [30, 60, 90, 180, 360])
            ->where('filters.organization_id', null)
            ->has('operatives', 0)
            ->has('organization_activity', 0)
            ->has('top_costumes', 0)
            ->where('costume', null));
    }

    public function test_invoke_defaults_to_all_time(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $this->emptyMetrics()]);

        $response = $this->actingAs($trooper)->get(route('service-records.leaderboard'));

        $messages->assertCalled(GetLeaderboardMetrics::class, fn (array $params): bool => $params['lookback'] === null
            && $params['organization'] === null);
        $response->assertInertia(fn (Assert $page) => $page->where('filters.days', null));
    }

    public function test_invoke_shows_the_selected_club_and_its_logo(): void
    {
        Storage::fake('public');
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $organization = Organization::factory()->asOrganization()->withName('Florida Garrison')->create([
            Organization::IMAGE_PATH_SM => 'organizations/florida-32x32.png',
        ]);
        Storage::disk('public')->put($organization->image_path_sm, 'logo');
        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $this->emptyMetrics()]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard', ['organization_id' => $organization->id]));

        $messages->assertCalled(
            GetLeaderboardMetrics::class,
            fn (array $params): bool => $params['organization']?->id === $organization->id
        );
        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.organization_id', $organization->id)
            ->where('filters.organization.name', 'Florida Garrison')
            ->where('filters.organization.logo_url', fn (string $url): bool => str_contains(
                $url,
                'storage/organizations/florida-32x32.png'
            ))
            ->where('filters.organizations', fn ($options): bool => collect($options)
                ->contains(fn (array $option): bool => $option === [
                    'value' => $organization->id,
                    'label' => 'Florida Garrison',
                ])));
    }

    public function test_invoke_lists_costumes_for_the_lookup(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->withName('Shadow Scout')->create();
        $this->fakeMessages([GetLeaderboardMetrics::class => $this->emptyMetrics()]);

        $response = $this->actingAs($trooper)->get(route('service-records.leaderboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('costumes', fn ($costumes): bool => collect($costumes)
                ->contains(fn (array $row): bool => $row === ['id' => $costume->id, 'name' => 'Shadow Scout'])));
    }

    public function test_invoke_ranks_troopers_with_their_real_name_when_it_differs(): void
    {
        $viewer = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $named = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'TK-Zeta', Trooper::LEGAL_NAME => 'Jane Example']);
        $same = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Sam Same', Trooper::LEGAL_NAME => 'Sam Same']);
        $this->troopToConfirm($named, EventTrooperStatus::ATTENDED);
        $this->troopToConfirm($same, EventTrooperStatus::ATTENDED);

        $response = $this->actingAs($viewer)->get(route('service-records.leaderboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('operatives', 2)
            ->where('operatives', function ($rows) use ($named, $same): bool
            {
                $rows = collect($rows)->keyBy('id');

                return $rows[$named->id]['legal_name'] === 'Jane Example'
                    && $rows[$named->id]['troop_count'] === 1
                    && $rows[$named->id]['url'] === route('service-records.trooper', ['trooper' => $named])
                    //  a real name that only repeats the username isn't sent
                    && $rows[$same->id]['legal_name'] === null;
            }));
    }

    public function test_a_partial_reload_opens_a_costume_without_counting_the_leaderboard_again(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->withName('Shadow Scout')->create();
        $this->fakeMessages([GetLeaderboardMetrics::class => $this->emptyMetrics()]);
        $version = $this->actingAs($trooper)->get(route('service-records.leaderboard'))
            ->viewData('page')['version'];
        $messages = $this->fakeMessages([
            GetLeaderboardMetrics::class => $this->emptyMetrics(),
            GetCostumeTrooperLeaderboard::class => [
                'top_troopers' => collect(),
                'stats' => ['total_deployments' => 1200, 'unique_troopers' => 3, 'last_deployed_at' => null],
            ],
        ]);

        $response = $this->actingAs($trooper)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'service-records/Leaderboard',
                'X-Inertia-Partial-Data' => 'costume',
            ])
            ->get(route('service-records.leaderboard', ['costume_id' => $costume->id, 'days' => 30]));

        $response->assertOk();
        $this->assertArrayNotHasKey('operatives', $response->json('props'));
        $this->assertSame([
            'id' => $costume->id,
            'name' => 'Shadow Scout',
            'total_deployments' => '1,200',
            'unique_troopers' => '3',
            'last_deployed' => null,
            'top_troopers' => [],
        ], $response->json('props.costume'));
        $messages->assertNotCalled(GetLeaderboardMetrics::class);
        $messages->assertCalled(
            GetCostumeTrooperLeaderboard::class,
            fn (array $params): bool => $params['costume']->is($costume) && $params['lookback'] === 30
        );
    }

    /**
     * @return array{dominance: \Illuminate\Support\Collection, diversity: \Illuminate\Support\Collection, operatives: \Illuminate\Support\Collection}
     */
    private function emptyMetrics(): array
    {
        return ['dominance' => collect(), 'diversity' => collect(), 'operatives' => collect()];
    }
}
