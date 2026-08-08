<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\QueueWorkerHeartbeatJob;
use Illuminate\Console\Command;

/**
 * Dispatches a heartbeat job onto the queue every minute.
 *
 * When a queue worker (kept alive by Supervisor) picks the job up, it stamps
 * a cache timestamp. If that timestamp goes stale, the queue worker process
 * has stopped consuming jobs.
 */
class DispatchQueueHeartbeatCommand extends Command
{
    protected $signature = 'tracker:dispatch-queue-heartbeat';

    protected $description = 'Dispatch a heartbeat job onto the queue to verify a worker is consuming jobs';

    public function handle(): int
    {
        QueueWorkerHeartbeatJob::dispatch();

        return Command::SUCCESS;
    }
}
