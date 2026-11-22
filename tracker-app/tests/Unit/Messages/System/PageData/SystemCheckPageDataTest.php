<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\System\PageData;

use App\Enums\SystemCheckStatus;
use App\Messages\System\PageData\SystemCheckPageData;
use App\Services\LogEntry;
use App\Services\LogViewerService;
use App\Services\SystemCheckResult;
use App\Services\SystemCheckService;
use Mockery;
use Tests\TestCase;

class SystemCheckPageDataTest extends TestCase
{
    public function test_handle_lists_each_check_and_recent_error(): void
    {
        $service = Mockery::mock(SystemCheckService::class);
        $service->shouldReceive('run')->once()->andReturn(collect([
            'Environment' => collect([
                new SystemCheckResult('Debug mode', SystemCheckStatus::WARN, 'APP_DEBUG is on'),
            ]),
        ]));
        $log_viewer = Mockery::mock(LogViewerService::class);
        $log_viewer->shouldReceive('recentErrors')->once()->andReturn(collect([
            new LogEntry('2026-11-22 10:00:00', 'production', 'error', "Boom\n#0 trace"),
        ]));

        $subject = new SystemCheckPageData;
        $result = $subject->handle($service, $log_viewer);

        $this->assertSame([[
            'name' => 'Environment',
            'results' => [[
                'label' => 'Debug mode',
                'detail' => 'APP_DEBUG is on',
                'status' => 'Warn',
                'badge' => 'bg-warning',
                'icon' => 'fa-triangle-exclamation',
            ]],
        ]], $result['groups']);
        $this->assertSame('ERROR', $result['errors'][0]['level']);
        $this->assertSame('Boom', $result['errors'][0]['summary']);
        $this->assertSame("Boom\n#0 trace", $result['errors'][0]['message']);
    }
}
