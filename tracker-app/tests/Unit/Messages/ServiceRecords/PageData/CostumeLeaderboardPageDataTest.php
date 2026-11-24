<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\Reports\Queries\GetCostumeTrooperLeaderboard;
use App\Messages\ServiceRecords\PageData\CostumeLeaderboardPageData;
use App\Models\Costume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class CostumeLeaderboardPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_returns_the_costume_leaderboard(): void
    {
        $costume = Costume::factory()->create();
        $fake = $this->fakeMessages([
            GetCostumeTrooperLeaderboard::class => [
                'top_troopers' => collect(),
                'stats' => ['total_deployments' => 3, 'unique_troopers' => 1, 'last_deployed_at' => null],
            ],
        ]);

        $subject = new CostumeLeaderboardPageData(costume: $costume, days: 'all');
        $result = $subject->handle();

        $stats = $result['costume']->resolve();
        $this->assertSame($costume->id, $stats['id']);
        $this->assertSame('3', $stats['total_deployments']);
        $this->assertSame([], $stats['top_troopers']);
        $this->assertNull($result['filters']->resolve()['days']);
        $fake->assertCalled(
            GetCostumeTrooperLeaderboard::class,
            fn (array $params): bool => $params['costume'] === $costume && $params['lookback'] === null
        );
    }
}
