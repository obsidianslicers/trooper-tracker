<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Jobs\QueueWorkerHeartbeatJob;
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
}
