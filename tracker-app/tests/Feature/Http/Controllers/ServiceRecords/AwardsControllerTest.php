<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Bus\MagicBus;
use App\Features\Troopers\Queries\GetTrooperAwardsQuery;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class AwardsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.awards'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_awards_with_default_days(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $award_troopers = collect([AwardTrooper::factory()->make()]);

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($award_troopers): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperAwardsQuery $query): bool
                {
                    return $query->lookback === 30;
                })
                ->andReturn($award_troopers);
        });

        $response = $this->actingAs($trooper)->get(route('service-records.awards'));

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
        $trooper = Trooper::factory()->asMember()->create();

        $this->mock(MagicBus::class, function (MockInterface $mock): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetTrooperAwardsQuery $query): bool
                {
                    return $query->lookback === 120;
                })
                ->andReturn(collect());
        });

        $response = $this->actingAs($trooper)
            ->get(route('service-records.awards', ['days' => 120]));

        $response->assertOk();
        $response->assertViewHas('days', 120);
    }
}
