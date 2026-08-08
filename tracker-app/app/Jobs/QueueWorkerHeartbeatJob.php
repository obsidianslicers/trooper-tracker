<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\QueueWorkerHeartbeatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Records a cache timestamp when processed, proving a queue worker is alive.
 *
 * Dispatched on a schedule by DispatchQueueHeartbeatCommand. Staleness of the
 * timestamp it writes is the liveness signal used by SystemCheckService and
 * CheckSupervisorHealthCommand to detect whether Supervisor is still keeping
 * the queue worker process running.
 */
class QueueWorkerHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public function handle(QueueWorkerHeartbeatService $heartbeat): void
    {
        $heartbeat->recordHeartbeat();
    }
}
