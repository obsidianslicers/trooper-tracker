<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\QueueWorkerHeartbeatJob;
use App\Services\QueueWorkerHeartbeatService;
use Tests\TestCase;

class QueueWorkerHeartbeatJobTest extends TestCase
{
    public function test_handle_records_a_heartbeat(): void
    {
        $heartbeat = app(QueueWorkerHeartbeatService::class);
        $this->assertNull($heartbeat->minutesSinceLastHeartbeat());

        $subject = new QueueWorkerHeartbeatJob;
        $subject->handle($heartbeat);

        $this->assertSame(0, $heartbeat->minutesSinceLastHeartbeat());
    }
}
