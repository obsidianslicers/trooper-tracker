<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Admin\System\SupervisorDown;
use App\Mail\Admin\System\SupervisorRecovered;
use App\Services\QueueWorkerHeartbeatService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
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

    public function handle(MagicBus $bus, QueueWorkerHeartbeatService $heartbeat): int
    {
        if (!(bool) config('tracker.supervisor_check.email_enabled', false))
        {
            return Command::SUCCESS;
        }

        $minutes_since_last_heartbeat = $heartbeat->minutesSinceLastHeartbeat();
        $down_threshold = (int) config('tracker.supervisor_check.heartbeat_down_minutes', 10);
        $is_down = $minutes_since_last_heartbeat === null || $minutes_since_last_heartbeat >= $down_threshold;
        $down_notified_at = $heartbeat->downNotifiedAt();

        if ($is_down)
        {
            $this->notifyDown($bus, $heartbeat, $down_notified_at, $minutes_since_last_heartbeat ?? $down_threshold);

            return Command::SUCCESS;
        }

        if ($down_notified_at !== null)
        {
            $this->notifyRecovered($bus, $heartbeat);
        }

        return Command::SUCCESS;
    }

    private function notifyDown(
        MagicBus $bus,
        QueueWorkerHeartbeatService $heartbeat,
        ?CarbonImmutable $down_notified_at,
        int $minutes_since_last_heartbeat): void
    {
        $renotify_after_minutes = (int) config('tracker.supervisor_check.renotify_after_minutes', 60);

        if ($down_notified_at !== null
            && $down_notified_at->diffInMinutes(CarbonImmutable::now()) < $renotify_after_minutes)
        {
            return;
        }

        foreach ($this->getAdmins($bus) as $admin)
        {
            Mail::to($admin->email)->send(new SupervisorDown($admin, $minutes_since_last_heartbeat));
        }

        $heartbeat->markDownNotified();

        $this->warn("Notified admins: queue worker heartbeat stale for {$minutes_since_last_heartbeat} minute(s).");
    }

    private function notifyRecovered(MagicBus $bus, QueueWorkerHeartbeatService $heartbeat): void
    {
        foreach ($this->getAdmins($bus) as $admin)
        {
            Mail::to($admin->email)->send(new SupervisorRecovered($admin));
        }

        $heartbeat->clearDownNotified();

        $this->info('Notified admins: queue worker heartbeat has recovered.');
    }

    private function getAdmins(MagicBus $bus): iterable
    {
        return $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));
    }
}
