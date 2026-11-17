<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Support\Queries;

use App\Messages\Support\Queries\GetSupportGoal;
use App\Services\Support\SupportStatusService;
use Mockery;
use Tests\TestCase;

class GetSupportGoalTest extends TestCase
{
    public function test_handle_returns_the_goal_progress_appeal_and_link(): void
    {
        config(['tracker.support.url' => 'https://example.test/give']);
        $service = Mockery::mock(SupportStatusService::class);
        $service->shouldReceive('calculate')->once()->andReturn([
            'goal' => 300.0,
            'current' => 75.0,
            'progress' => 25.0,
            'uses_donation_sources' => false,
        ]);

        $subject = new GetSupportGoal;
        $result = $subject->handle($service);

        $this->assertSame(300.0, $result['goal']);
        $this->assertSame(75.0, $result['current']);
        $this->assertSame(25.0, $result['progress']);
        $this->assertSame('https://example.test/give', $result['url']);
        $this->assertStringContainsString(config('app.name'), $result['message']);
    }
}
