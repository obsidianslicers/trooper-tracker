<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Messages\Reports\Queries\GetLeaderboardMetrics;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class LeaderboardControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.leaderboard'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_leaderboard_with_requested_days(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $leaderboard = collect([
            'dominance' => collect(),
            'diversity' => collect(),
            'operatives' => collect(),
        ]);

        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $leaderboard]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard', ['days' => 90]));

        $messages->assertCalled(GetLeaderboardMetrics::class, fn (array $params): bool => $params['lookback'] === 90 && $params['organization'] === null && $params['limit'] === 30);

        $response->assertOk();
        $response->assertViewIs('pages.service-records.leaderboard');
        $response->assertViewHas('days', 90);
        $response->assertViewHas('organization_id', null);
        $response->assertViewHas('leaderboard', function (Collection $result): bool
        {
            return $result->get('dominance') instanceof Collection
                && $result->get('diversity') instanceof Collection
                && $result->get('operatives') instanceof Collection;
        });
    }

    public function test_invoke_defaults_to_all_time(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $leaderboard = collect([
            'dominance' => collect(),
            'diversity' => collect(),
            'operatives' => collect(),
        ]);

        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $leaderboard]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard'));

        $messages->assertCalled(GetLeaderboardMetrics::class, fn (array $params): bool => $params['lookback'] === null && $params['organization'] === null && $params['limit'] === 30);

        $response->assertOk();
        $response->assertViewHas('days', null);
    }

    public function test_invoke_resolves_selected_top_level_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $organization = Organization::factory()->asOrganization()->create([
            Organization::IMAGE_PATH_LG => 'organizations/florida-128x128.png',
            Organization::IMAGE_PATH_SM => 'organizations/florida-32x32.png',
        ]);

        $leaderboard = collect([
            'dominance' => collect(),
            'diversity' => collect(),
            'operatives' => collect(),
        ]);

        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $leaderboard]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard', ['organization_id' => $organization->id]));

        $messages->assertCalled(GetLeaderboardMetrics::class, fn (array $params): bool => $params['lookback'] === null
                && $params['organization']?->id === $organization->id
                && $params['limit'] === 30);

        $response->assertOk();
        $response->assertViewHas('organization_id', $organization->id);
        $response->assertViewHas(
            'organization',
            function (?Organization $selected_organization) use ($organization): bool
            {
                return $selected_organization?->id === $organization->id
                    && $selected_organization->image_path_lg === 'organizations/florida-128x128.png'
                    && $selected_organization->image_path_sm === 'organizations/florida-32x32.png';
            }
        );
        $response->assertViewHas(
            'organizations',
            function (Collection $organizations) use ($organization): bool
            {
                $selected_organization = $organizations->firstWhere('id', $organization->id);

                return $selected_organization !== null
                    && $selected_organization->image_path_lg === 'organizations/florida-128x128.png'
                    && $selected_organization->image_path_sm === 'organizations/florida-32x32.png';
            }
        );
    }

    public function test_invoke_renders_selected_organization_logo_context(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $organization = Organization::factory()->asOrganization()->withName('Florida Garrison')->create([
            Organization::IMAGE_PATH_LG => 'organizations/florida-128x128.png',
            Organization::IMAGE_PATH_SM => 'organizations/florida-32x32.png',
        ]);
        Storage::disk('public')->put($organization->image_path_sm, 'logo');

        $leaderboard = collect([
            'dominance' => collect(),
            'diversity' => collect(),
            'operatives' => collect(),
        ]);

        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $leaderboard]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard', ['organization_id' => $organization->id]));

        $messages->assertCalled(GetLeaderboardMetrics::class);

        $response->assertOk();
        $response->assertSee('Florida Garrison');
        $response->assertSee('storage/organizations/florida-32x32.png');
    }

    public function test_invoke_passes_costume_list_to_view(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->withName('Shadow Scout')->create();

        $leaderboard = collect([
            'dominance' => collect(),
            'diversity' => collect(),
            'operatives' => collect(),
        ]);

        $messages = $this->fakeMessages([GetLeaderboardMetrics::class => $leaderboard]);

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard'));

        $messages->assertCalled(GetLeaderboardMetrics::class);

        $response->assertOk();
        $response->assertViewHas('costume_list', function (Collection $costume_list) use ($costume): bool
        {
            return $costume_list->contains('name', $costume->name);
        });
    }
}
