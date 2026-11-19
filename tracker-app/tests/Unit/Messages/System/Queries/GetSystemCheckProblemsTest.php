<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\System\Queries;

use App\Enums\SystemCheckStatus;
use App\Messages\System\Queries\GetSystemCheckProblems;
use App\Services\SystemCheckResult;
use App\Services\SystemCheckService;
use Mockery;
use Tests\TestCase;

class GetSystemCheckProblemsTest extends TestCase
{
    public function test_handle_returns_only_problems_with_failures_first(): void
    {
        $service = Mockery::mock(SystemCheckService::class);
        $service->shouldReceive('run')->once()->andReturn(collect([
            'Environment' => collect([
                new SystemCheckResult('APP_KEY set', SystemCheckStatus::PASS),
                new SystemCheckResult('Debug mode', SystemCheckStatus::WARN, 'APP_DEBUG is enabled'),
            ]),
            'Database & Cache' => collect([
                new SystemCheckResult('Queue worker heartbeat', SystemCheckStatus::FAIL, 'Last seen 12'),
            ]),
        ]));

        $subject = new GetSystemCheckProblems;
        $result = $subject->handle($service);

        $this->assertSame(['Queue worker heartbeat', 'Debug mode'], $result->pluck('label')->all());
    }

    public function test_handle_returns_empty_when_everything_passes(): void
    {
        $service = Mockery::mock(SystemCheckService::class);
        $service->shouldReceive('run')->once()->andReturn(collect([
            'Environment' => collect([new SystemCheckResult('APP_KEY set', SystemCheckStatus::PASS)]),
        ]));

        $subject = new GetSystemCheckProblems;

        $this->assertTrue($subject->handle($service)->isEmpty());
    }
}
