<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Bus\MagicBus;
use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class LeaderboardControllerTest extends TestCase
{
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

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($leaderboard): void {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetLeaderboardMetricsQuery $query): bool {
                    return $query->lookback === 90
                        && $query->organization === null
                        && $query->limit === 30;
                })
                ->andReturn($leaderboard);
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard', ['days' => 90]));

        $response->assertOk();
        $response->assertViewIs('pages.service-records.leaderboard');
        $response->assertViewHas('days', 90);
        $response->assertViewHas('organization_id', null);
        $response->assertViewHas('leaderboard', function (Collection $result): bool {
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

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($leaderboard): void {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetLeaderboardMetricsQuery $query): bool {
                    return $query->lookback === null
                        && $query->organization === null
                        && $query->limit === 30;
                })
                ->andReturn($leaderboard);
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard'));

        $response->assertOk();
        $response->assertViewHas('days', null);
    }

    public function test_invoke_resolves_selected_top_level_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $leaderboard = collect([
            'dominance' => collect(),
            'diversity' => collect(),
            'operatives' => collect(),
        ]);

        $this->mock(
            MagicBus::class,
            function (MockInterface $mock) use ($leaderboard, $organization): void {
                $query_matches = function (
                    GetLeaderboardMetricsQuery $query
                ) use ($organization): bool {
                    return $query->lookback === null
                        && $query->organization?->id === $organization->id
                        && $query->limit === 30;
                };

                $mock->shouldReceive('send')
                    ->once()
                    ->withArgs($query_matches)
                    ->andReturn($leaderboard);
            }
        );

        $response = $this->actingAs($trooper)
            ->get(route('service-records.leaderboard', ['organization_id' => $organization->id]));

        $response->assertOk();
        $response->assertViewHas('organization_id', $organization->id);
        $response->assertViewHas(
            'organizations',
            function (Collection $organizations) use ($organization): bool {
                return $organizations->contains('id', $organization->id);
            }
        );
    }
}
