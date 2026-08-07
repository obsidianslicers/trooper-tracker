<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\QueueWorkerHeartbeatService;
use Tests\TestCase;

class QueueWorkerHeartbeatServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tracker.supervisor_check.heartbeat_down_minutes' => 10]);
        config(['tracker.supervisor_check.renotify_after_minutes' => 60]);
    }

    public function test_minutes_since_last_heartbeat_is_null_when_never_recorded(): void
    {
        $subject = new QueueWorkerHeartbeatService;

        $this->assertNull($subject->lastHeartbeatAt());
        $this->assertNull($subject->minutesSinceLastHeartbeat());
    }

    public function test_record_heartbeat_makes_minutes_since_last_heartbeat_zero(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->recordHeartbeat();

        $this->assertSame(0, $subject->minutesSinceLastHeartbeat());
    }

    public function test_is_down_when_no_heartbeat_has_been_recorded(): void
    {
        $subject = new QueueWorkerHeartbeatService;

        $this->assertTrue($subject->isDown());
    }

    public function test_is_not_down_when_heartbeat_is_within_threshold(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->recordHeartbeat();
        $this->travel(5)->minutes();

        $this->assertFalse($subject->isDown());
    }

    public function test_is_down_when_heartbeat_exceeds_threshold(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->recordHeartbeat();
        $this->travel(11)->minutes();

        $this->assertTrue($subject->isDown());
    }

    public function test_should_send_down_alert_when_down_and_never_notified(): void
    {
        $subject = new QueueWorkerHeartbeatService;

        $this->assertTrue($subject->shouldSendDownAlert());
    }

    public function test_should_not_send_down_alert_when_not_down(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->recordHeartbeat();

        $this->assertFalse($subject->shouldSendDownAlert());
    }

    public function test_should_not_resend_down_alert_within_renotify_window(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->markDownNotified();

        $this->assertFalse($subject->shouldSendDownAlert());
    }

    public function test_should_resend_down_alert_after_renotify_window_elapses(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->markDownNotified();
        $this->travel(61)->minutes();

        $this->assertTrue($subject->shouldSendDownAlert());
    }

    public function test_should_send_recovery_alert_when_fresh_and_previously_notified(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->markDownNotified();
        $subject->recordHeartbeat();

        $this->assertTrue($subject->shouldSendRecoveryAlert());
    }

    public function test_should_not_send_recovery_alert_when_never_notified(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->recordHeartbeat();

        $this->assertFalse($subject->shouldSendRecoveryAlert());
    }

    public function test_clear_down_notified_removes_the_notified_marker(): void
    {
        $subject = new QueueWorkerHeartbeatService;
        $subject->markDownNotified();
        $this->assertNotNull($subject->downNotifiedAt());

        $subject->clearDownNotified();

        $this->assertNull($subject->downNotifiedAt());
    }
}
