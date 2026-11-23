<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\PageData\AchievementsPageData;
use App\Messages\Troopers\Queries\GetTrooperAchievements;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class AchievementsPageDataTest extends TestCase
{
    use FakesMessages;

    public function test_handle_looks_back_the_requested_days(): void
    {
        $achievements = collect(['milestone']);
        $fake = $this->fakeMessages([GetTrooperAchievements::class => $achievements]);

        $subject = new AchievementsPageData(days: 60);
        $result = $subject->handle();

        $this->assertSame(['days' => 60, 'trooper_achievements' => $achievements], $result);
        $fake->assertCalled(GetTrooperAchievements::class, fn (array $params): bool => $params['lookback'] === 60);
    }

    public function test_handle_looks_back_thirty_days_by_default(): void
    {
        $this->fakeMessages([GetTrooperAchievements::class => collect()]);

        $subject = new AchievementsPageData;

        $this->assertSame(30, $subject->handle()['days']);
    }
}
