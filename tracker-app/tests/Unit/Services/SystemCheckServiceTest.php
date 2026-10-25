<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\SystemCheckStatus;
use App\Services\QueueWorkerHeartbeatService;
use App\Services\SystemCheckResult;
use App\Services\SystemCheckService;
use ReflectionMethod;
use Tests\TestCase;

class SystemCheckServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests default QUEUE_CONNECTION to "sync"; these tests exercise the
        // async-worker path, so force a driver that requires a real worker.
        config(['queue.default' => 'database']);
    }

    public function test_returns_fail_when_no_heartbeat_has_been_recorded(): void
    {
        $result = $this->checkQueueWorkerHeartbeat();

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
    }

    public function test_returns_pass_when_queue_driver_is_sync_regardless_of_heartbeat(): void
    {
        config(['queue.default' => 'sync']);

        $result = $this->checkQueueWorkerHeartbeat();

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
    }

    public function test_returns_pass_when_heartbeat_is_recent(): void
    {
        config([
            'tracker.supervisor_check.heartbeat_warn_minutes' => 3,
            'tracker.supervisor_check.heartbeat_down_minutes' => 10,
        ]);
        app(QueueWorkerHeartbeatService::class)->recordHeartbeat();

        $result = $this->checkQueueWorkerHeartbeat();

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
    }

    public function test_returns_warn_when_heartbeat_exceeds_warn_threshold(): void
    {
        config([
            'tracker.supervisor_check.heartbeat_warn_minutes' => 3,
            'tracker.supervisor_check.heartbeat_down_minutes' => 10,
        ]);
        app(QueueWorkerHeartbeatService::class)->recordHeartbeat();
        $this->travel(5)->minutes();

        $result = $this->checkQueueWorkerHeartbeat();

        $this->assertSame(SystemCheckStatus::WARN, $result->status);
    }

    public function test_returns_fail_when_heartbeat_exceeds_down_threshold(): void
    {
        config([
            'tracker.supervisor_check.heartbeat_warn_minutes' => 3,
            'tracker.supervisor_check.heartbeat_down_minutes' => 10,
        ]);
        app(QueueWorkerHeartbeatService::class)->recordHeartbeat();
        $this->travel(11)->minutes();

        $result = $this->checkQueueWorkerHeartbeat();

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
    }

    public function test_scheduler_check_fails_when_cron_has_never_run(): void
    {
        $result = $this->invokeCheck('checkSchedulerHeartbeat');

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
    }

    public function test_scheduler_check_passes_after_recent_heartbeat(): void
    {
        app(QueueWorkerHeartbeatService::class)->recordSchedulerHeartbeat();

        $result = $this->invokeCheck('checkSchedulerHeartbeat');

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
    }

    public function test_legacy_path_check_fails_when_a_path_uses_the_old_prefix(): void
    {
        config(['logging.channels.single.path' => '/home/bitnami/htdocs/storage/logs/laravel.log']);

        $result = $this->invokeCheck('checkLegacyPaths');

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
        $this->assertStringContainsString('/home/bitnami/htdocs', (string) $result->detail);
    }

    public function test_legacy_path_check_passes_on_a_clean_install(): void
    {
        $result = $this->invokeCheck('checkLegacyPaths');

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
    }

    public function test_xenforo_group_is_hidden_when_xenforo_is_not_configured(): void
    {
        config([
            'services.xenforo.client_id' => null,
            'services.xenforo.base_url' => '',
            'services.xenforo.api_key' => null,
        ]);

        $groups = app(SystemCheckService::class)->run();

        $this->assertFalse($groups->has('XenForo'));
        $labels = $groups->flatten()->map(fn (SystemCheckResult $result): string => $result->label);
        $this->assertFalse($labels->contains(fn (string $label): bool => str_contains($label, 'XenForo')));
    }

    public function test_xenforo_group_is_shown_when_xenforo_oauth_is_configured(): void
    {
        config([
            'services.xenforo.client_id' => 'id',
            'services.xenforo.client_secret' => 'secret',
            'services.xenforo.redirect' => 'https://tracker.test/callback',
            'services.xenforo.base_url' => '',
            'services.xenforo.api_key' => null,
        ]);

        $groups = app(SystemCheckService::class)->run();

        $this->assertTrue($groups->has('XenForo'));
    }

    private function checkQueueWorkerHeartbeat(): SystemCheckResult
    {
        return $this->invokeCheck('checkQueueWorkerHeartbeat');
    }

    private function invokeCheck(string $method_name): SystemCheckResult
    {
        $subject = app(SystemCheckService::class);
        $method = new ReflectionMethod($subject, $method_name);

        return $method->invoke($subject);
    }
}
