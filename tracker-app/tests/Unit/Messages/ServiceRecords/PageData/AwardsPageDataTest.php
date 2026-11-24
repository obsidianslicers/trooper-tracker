<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\PageData\AwardsPageData;
use App\Messages\Troopers\Queries\GetTrooperAwards;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class AwardsPageDataTest extends TestCase
{
    use FakesMessages;

    public function test_handle_looks_back_the_requested_days(): void
    {
        $awards = collect(['award']);
        $fake = $this->fakeMessages([GetTrooperAwards::class => $awards]);

        $subject = new AwardsPageData(days: 90);
        $result = $subject->handle();

        $this->assertSame(90, $result['days']);
        $this->assertSame($awards->all(), $result['awards']->collection->map->resource->all());
        $fake->assertCalled(GetTrooperAwards::class, fn (array $params): bool => $params['lookback'] === 90);
    }
}
