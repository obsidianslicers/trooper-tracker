<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Bus\MagicBus;
use App\Features\Reports\Queries\GetCostumeTrooperLeaderboardQuery;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class CostumeControllerTest extends TestCase
{
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

        $this->mock(MagicBus::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn($this->handlerResult());
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $response->assertOk();
        $response->assertViewIs('pages.service-records.costume');
    }

    public function test_invoke_renders_partial_view_for_htmx_request(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $this->mock(MagicBus::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn($this->handlerResult());
        });

        $response = $this->actingAs($trooper)
            ->withHeader('HX-Request', 'true')
            ->get(route('service-records.costume', $costume));

        $response->assertOk();
        $response->assertViewIs('pages.service-records.inc.costume-stats');
    }

    public function test_invoke_resolves_days_from_query_string(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $this->mock(
            MagicBus::class,
            function (MockInterface $mock) use ($costume): void {
                $mock->shouldReceive('send')
                    ->once()
                    ->withArgs(function (GetCostumeTrooperLeaderboardQuery $query) use ($costume): bool {
                        return $query->costume->id === $costume->id
                            && $query->lookback === 90
                            && $query->organization === null
                            && $query->limit === 30;
                    })
                    ->andReturn($this->handlerResult());
            }
        );

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', ['costume' => $costume, 'days' => 90]));

        $response->assertOk();
        $response->assertViewHas('days', 90);
    }

    public function test_invoke_defaults_to_all_time_when_days_absent(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $this->mock(
            MagicBus::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('send')
                    ->once()
                    ->withArgs(function (GetCostumeTrooperLeaderboardQuery $query): bool {
                        return $query->lookback === null;
                    })
                    ->andReturn($this->handlerResult());
            }
        );

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $response->assertOk();
        $response->assertViewHas('days', null);
    }

    public function test_invoke_ignores_invalid_days_value(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $this->mock(
            MagicBus::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('send')
                    ->once()
                    ->withArgs(function (GetCostumeTrooperLeaderboardQuery $query): bool {
                        return $query->lookback === null;
                    })
                    ->andReturn($this->handlerResult());
            }
        );

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', ['costume' => $costume, 'days' => 999]));

        $response->assertOk();
        $response->assertViewHas('days', null);
    }

    public function test_invoke_resolves_organization_from_query_string(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $this->mock(
            MagicBus::class,
            function (MockInterface $mock) use ($organization): void {
                $mock->shouldReceive('send')
                    ->once()
                    ->withArgs(function (GetCostumeTrooperLeaderboardQuery $query) use ($organization): bool {
                        return $query->organization?->id === $organization->id;
                    })
                    ->andReturn($this->handlerResult());
            }
        );

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', [
                'costume' => $costume,
                'organization_id' => $organization->id,
            ]));

        $response->assertOk();
        $response->assertViewHas('organization_id', $organization->id);
    }

    public function test_invoke_passes_costume_to_view(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $this->mock(MagicBus::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn($this->handlerResult());
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $response->assertOk();
        $response->assertViewHas('costume', function (Costume $view_costume) use ($costume): bool {
            return $view_costume->id === $costume->id;
        });
    }

    public function test_invoke_passes_stats_and_top_troopers_to_view(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $costume = Costume::factory()->create();

        $this->mock(MagicBus::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn($this->handlerResult());
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.costume', $costume));

        $response->assertOk();
        $response->assertViewHas('top_troopers', function (Collection $top_troopers): bool {
            return $top_troopers->isEmpty();
        });
        $response->assertViewHas('stats', function (array $stats): bool {
            return array_key_exists('total_deployments', $stats)
                && array_key_exists('unique_troopers', $stats)
                && array_key_exists('last_deployed_at', $stats);
        });
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
                'unique_troopers'   => 0,
                'last_deployed_at'  => null,
            ],
        ];
    }
}
