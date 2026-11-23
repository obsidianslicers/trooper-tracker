<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Support\PageData;

use App\Messages\Support\PageData\SupportGoalWidgetPageData;
use App\Messages\Support\Queries\GetSupportGoal;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SupportGoalWidgetPageDataTest extends TestCase
{
    use FakesMessages;

    public function test_handle_rounds_the_progress_for_display(): void
    {
        $this->fakeMessages([GetSupportGoal::class => [
            'goal' => 500,
            'current' => 333,
            'progress' => 66.6,
            'message' => 'Keep going',
        ]]);

        $subject = new SupportGoalWidgetPageData;

        $this->assertSame([
            'goal' => 500,
            'current' => 333,
            'progress' => '67',
            'message' => 'Keep going',
        ], $subject->handle());
    }
}
