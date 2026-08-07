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
    public function test_returns_fail_when_no_heartbeat_has_been_recorded(): void
    {
        $result = $this->checkQueueWorkerHeartbeat();

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
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

    private function checkQueueWorkerHeartbeat(): SystemCheckResult
    {
        $subject = app(SystemCheckService::class);
        $method = new ReflectionMethod($subject, 'checkQueueWorkerHeartbeat');

        return $method->invoke($subject);
    }
}
