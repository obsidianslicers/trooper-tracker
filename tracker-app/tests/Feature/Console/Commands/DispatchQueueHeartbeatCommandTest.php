<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Jobs\QueueWorkerHeartbeatJob;
use App\Services\QueueWorkerHeartbeatService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchQueueHeartbeatCommandTest extends TestCase
{
    public function test_dispatches_the_heartbeat_job(): void
    {
        Queue::fake();

        $this->artisan('tracker:dispatch-queue-heartbeat')->assertExitCode(0);

        Queue::assertPushed(QueueWorkerHeartbeatJob::class);
    }

    public function test_records_the_scheduler_heartbeat(): void
    {
        Queue::fake();

        $this->artisan('tracker:dispatch-queue-heartbeat')->assertExitCode(0);

        $this->assertSame(0, app(QueueWorkerHeartbeatService::class)->minutesSinceSchedulerHeartbeat());
    }
}
