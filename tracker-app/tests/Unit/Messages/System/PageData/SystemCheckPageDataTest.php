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
        $log_viewer->shouldReceive('recentErrors')->once()->with(26)->andReturn(collect([
            new LogEntry('2026-11-22 10:00:00', 'production', 'error', "Boom\n#0 trace"),
        ]));

        $subject = new SystemCheckPageData;
        //  as the page receives it, with Resources resolved
        $data = $subject->handle($service, $log_viewer);
        $data['groups'] = $data['groups']();
        $result = json_decode(json_encode($data), true);

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
        $this->assertSame('ERROR', $result['log_entries'][0]['level']);
        $this->assertSame('Boom', $result['log_entries'][0]['summary']);
        $this->assertSame("Boom\n#0 trace", $result['log_entries'][0]['message']);
        $this->assertFalse($result['has_more_errors']);
    }

    public function test_handle_shows_the_requested_number_of_errors_and_whether_there_are_more(): void
    {
        $service = Mockery::mock(SystemCheckService::class);
        $service->shouldNotReceive('run');
        $log_viewer = Mockery::mock(LogViewerService::class);
        $log_viewer->shouldReceive('recentErrors')->once()->with(51)->andReturn(
            collect(range(1, 51))->map(fn (int $i) => new LogEntry('2026-11-22 10:00:00', 'production', 'error', "Boom {$i}"))
        );

        $subject = new SystemCheckPageData(errors: 50);
        $result = json_decode(json_encode(collect($subject->handle($service, $log_viewer))->except('groups')), true);

        $this->assertCount(50, $result['log_entries']);
        $this->assertTrue($result['has_more_errors']);
    }

    public function test_handle_keeps_the_number_of_errors_within_bounds(): void
    {
        $service = Mockery::mock(SystemCheckService::class);
        $log_viewer = Mockery::mock(LogViewerService::class);
        $log_viewer->shouldReceive('recentErrors')->once()->with(26)->andReturn(collect());
        $log_viewer->shouldReceive('recentErrors')->once()->with(501)->andReturn(collect());

        (new SystemCheckPageData(errors: -5))->handle($service, $log_viewer);
        (new SystemCheckPageData(errors: 100000))->handle($service, $log_viewer);
    }
}
