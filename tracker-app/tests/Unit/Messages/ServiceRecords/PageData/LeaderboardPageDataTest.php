<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\Reports\Queries\GetLeaderboardMetrics;
use App\Messages\ServiceRecords\PageData\LeaderboardPageData;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class LeaderboardPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_filters_by_the_chosen_period_and_club(): void
    {
        $club = Organization::factory()->create();
        $fake = $this->fakeMessages([GetLeaderboardMetrics::class => ['rows' => []]]);

        $subject = new LeaderboardPageData(days: '90', organization_id: $club->id);
        $result = $subject->handle();

        $this->assertSame(90, $result['days']);
        $this->assertSame($club->id, $result['organization_id']);
        $this->assertSame(['rows' => []], $result['leaderboard']);
        $fake->assertCalled(GetLeaderboardMetrics::class, fn (array $params): bool => $params['lookback'] === 90
            && $params['organization']->is($club)
            && $params['limit'] === LeaderboardPageData::LIMIT);
    }

    public function test_handle_falls_back_to_all_time_and_every_club(): void
    {
        $this->fakeMessages([GetLeaderboardMetrics::class => []]);

        $subject = new LeaderboardPageData(days: '45', organization_id: 999);
        $result = $subject->handle();

        $this->assertNull($result['days']);
        $this->assertNull($result['organization_id']);
        $this->assertNull($result['organization']);
    }

    public function test_handle_lists_costumes_but_not_the_handler(): void
    {
        $this->fakeMessages([GetLeaderboardMetrics::class => []]);
        Costume::factory()->withName('Stormtrooper')->create();
        Costume::factory()->withName(Costume::HANDLER)->create();

        $result = (new LeaderboardPageData)->handle();

        $this->assertSame(['Stormtrooper'], $result['costume_list']->pluck('name')->all());
    }
}
