<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\Reports\Queries\GetCostumeTrooperLeaderboard;
use App\Messages\Reports\Queries\GetLeaderboardMetrics;
use App\Messages\ServiceRecords\PageData\LeaderboardPageData;
use App\Messages\ServiceRecords\Resources\CostumeStatsResource;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class LeaderboardPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_filters_by_the_chosen_period_and_club(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $fake = $this->fakeMessages([GetLeaderboardMetrics::class => $this->metrics()]);

        $subject = new LeaderboardPageData(days: '90', organization_id: $club->id);
        $result = $subject->handle();
        $filters = $result['filters']->resolve();

        $this->assertSame(90, $filters['days']);
        $this->assertSame($club->id, $filters['organization_id']);
        $this->assertSame([], $result['operatives']()->resolve());
        $fake->assertCalled(GetLeaderboardMetrics::class, fn (array $params): bool => $params['lookback'] === 90
            && $params['organization']->is($club)
            && $params['limit'] === LeaderboardPageData::LIMIT);
    }

    public function test_handle_falls_back_to_all_time_and_every_club(): void
    {
        $this->fakeMessages([GetLeaderboardMetrics::class => $this->metrics()]);

        $subject = new LeaderboardPageData(days: '45', organization_id: 999);
        $filters = $subject->handle()['filters']->resolve();

        $this->assertNull($filters['days']);
        $this->assertNull($filters['organization_id']);
        $this->assertNull($filters['organization']);
    }

    public function test_handle_counts_the_leaderboard_once_for_all_its_sections(): void
    {
        $fake = $this->fakeMessages([GetLeaderboardMetrics::class => $this->metrics(
            diversity: collect([['id' => 4, 'name' => 'Stormtrooper', 'count' => 12]]),
        )]);

        $result = (new LeaderboardPageData)->handle();

        $this->assertSame([['id' => 4, 'name' => 'Stormtrooper', 'count' => 12]], $result['top_costumes']()->resolve());
        $this->assertSame([], $result['organization_activity']()->resolve());
        $this->assertSame([], $result['operatives']()->resolve());
        $fake->assertCalled(GetLeaderboardMetrics::class, times: 1);
    }

    public function test_handle_lists_costumes_but_not_the_handler(): void
    {
        $this->fakeMessages([GetLeaderboardMetrics::class => $this->metrics()]);
        Costume::factory()->withName('Stormtrooper')->create();
        Costume::factory()->withName(Costume::HANDLER)->create();

        $result = (new LeaderboardPageData)->handle();

        $this->assertSame(['Stormtrooper'], collect($result['costumes']()->resolve())->pluck('name')->all());
    }

    public function test_handle_opens_the_chosen_costumes_rankings(): void
    {
        $costume = Costume::factory()->create();
        $fake = $this->fakeMessages([
            GetLeaderboardMetrics::class => $this->metrics(),
            GetCostumeTrooperLeaderboard::class => [
                'top_troopers' => collect(),
                'stats' => ['total_deployments' => 2, 'unique_troopers' => 1, 'last_deployed_at' => null],
            ],
        ]);

        $result = (new LeaderboardPageData(days: '30', costume_id: $costume->id))->handle();

        $this->assertInstanceOf(CostumeStatsResource::class, $result['costume']());
        $fake->assertCalled(
            GetCostumeTrooperLeaderboard::class,
            fn (array $params): bool => $params['costume']->is($costume) && $params['lookback'] === 30
        );
        $fake->assertNotCalled(GetLeaderboardMetrics::class);
    }

    public function test_handle_has_no_costume_panel_without_a_costume(): void
    {
        $this->fakeMessages([GetLeaderboardMetrics::class => $this->metrics()]);

        $this->assertNull((new LeaderboardPageData(costume_id: 999))->handle()['costume']());
    }

    /**
     * @return array{dominance: Collection, diversity: Collection, operatives: Collection}
     */
    private function metrics(?Collection $diversity = null): array
    {
        return ['dominance' => collect(), 'diversity' => $diversity ?? collect(), 'operatives' => collect()];
    }
}
