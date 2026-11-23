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
            GetCostumeTrooperLeaderboard::class => ['top_troopers' => ['TK-1'], 'stats' => ['troops' => 3]],
        ]);

        $subject = new CostumeLeaderboardPageData(costume: $costume, days: 'all');
        $result = $subject->handle();

        $this->assertSame($costume, $result['costume']);
        $this->assertSame(['TK-1'], $result['top_troopers']);
        $this->assertSame(['troops' => 3], $result['stats']);
        $this->assertNull($result['days']);
        $fake->assertCalled(
            GetCostumeTrooperLeaderboard::class,
            fn (array $params): bool => $params['costume'] === $costume && $params['lookback'] === null
        );
    }
}
