<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MembershipRole;
use App\Mail\Admin\System\SupervisorDown;
use App\Mail\Admin\System\SupervisorRecovered;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Services\QueueWorkerHeartbeatService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Detects whether the queue worker heartbeat has gone stale for longer than
 * the configured threshold, and - when TRACKER_SUPERVISOR_EMAIL_ENABLED is
 * on - emails administrators about the outage and its recovery.
 *
 * Sends emails synchronously (not queued): this command exists specifically
 * to alert when the queue itself may be down, so the alert cannot depend on
 * the queue to be delivered.
 */
class CheckSupervisorHealthCommand extends Command
{
    protected $signature = 'tracker:check-supervisor-health';

    protected $description = 'Check queue worker heartbeat staleness and email admins if it is down or recovered';

    public function handle(QueueWorkerHeartbeatService $heartbeat): int
    {
        if (!(bool) config('tracker.supervisor_check.email_enabled', false))
        {
            return Command::SUCCESS;
        }

        if ((string) config('queue.default') === 'sync')
        {
            return Command::SUCCESS;
        }

        if ($heartbeat->shouldSendDownAlert())
        {
            $this->notifyDown($heartbeat);

            return Command::SUCCESS;
        }

        if ($heartbeat->shouldSendRecoveryAlert())
        {
            $this->notifyRecovered($heartbeat);
        }

        return Command::SUCCESS;
    }

    private function notifyDown(QueueWorkerHeartbeatService $heartbeat): void
    {
        $down_threshold = (int) config('tracker.supervisor_check.heartbeat_down_minutes', 10);
        $minutes_since_last_heartbeat = $heartbeat->minutesSinceLastHeartbeat() ?? $down_threshold;

        foreach ($this->getAdmins() as $admin)
        {
            Mail::to($admin->email)->send(new SupervisorDown($admin, $minutes_since_last_heartbeat));
        }

        $heartbeat->markDownNotified();

        $this->warn("Notified admins: queue worker heartbeat stale for {$minutes_since_last_heartbeat} minute(s).");
    }

    private function notifyRecovered(QueueWorkerHeartbeatService $heartbeat): void
    {
        foreach ($this->getAdmins() as $admin)
        {
            Mail::to($admin->email)->send(new SupervisorRecovered($admin));
        }

        $heartbeat->clearDownNotified();

        $this->info('Notified admins: queue worker heartbeat has recovered.');
    }

    private function getAdmins(): Collection
    {
        return GetTroopersByRole::call(membership_role: MembershipRole::ADMINISTRATOR);
    }
}
