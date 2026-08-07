<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class QueueWorkerHeartbeatService
{
    public const CACHE_KEY = 'tracker:queue_worker_heartbeat';

    public const DOWN_NOTIFIED_CACHE_KEY = 'tracker:queue_worker_down_notified_at';

    private const CACHE_TTL_SECONDS = 86400;

    public function recordHeartbeat(): void
    {
        Cache::put(self::CACHE_KEY, CarbonImmutable::now(), self::CACHE_TTL_SECONDS);
    }

    public function lastHeartbeatAt(): ?CarbonImmutable
    {
        $value = Cache::get(self::CACHE_KEY);

        return $value instanceof CarbonImmutable ? $value : null;
    }

    public function minutesSinceLastHeartbeat(): ?int
    {
        $last_heartbeat_at = $this->lastHeartbeatAt();

        return $last_heartbeat_at === null ? null : (int) $last_heartbeat_at->diffInMinutes(CarbonImmutable::now());
    }

    public function markDownNotified(): void
    {
        Cache::put(self::DOWN_NOTIFIED_CACHE_KEY, CarbonImmutable::now(), self::CACHE_TTL_SECONDS);
    }

    public function clearDownNotified(): void
    {
        Cache::forget(self::DOWN_NOTIFIED_CACHE_KEY);
    }

    public function downNotifiedAt(): ?CarbonImmutable
    {
        $value = Cache::get(self::DOWN_NOTIFIED_CACHE_KEY);

        return $value instanceof CarbonImmutable ? $value : null;
    }

    public function isDown(): bool
    {
        $minutes_since_last_heartbeat = $this->minutesSinceLastHeartbeat();
        $down_minutes = (int) config('tracker.supervisor_check.heartbeat_down_minutes', 10);

        return $minutes_since_last_heartbeat === null || $minutes_since_last_heartbeat >= $down_minutes;
    }

    public function shouldSendDownAlert(): bool
    {
        if (!$this->isDown())
        {
            return false;
        }

        $down_notified_at = $this->downNotifiedAt();

        if ($down_notified_at === null)
        {
            return true;
        }

        $renotify_after_minutes = (int) config('tracker.supervisor_check.renotify_after_minutes', 60);

        return $down_notified_at->diffInMinutes(CarbonImmutable::now()) >= $renotify_after_minutes;
    }

    public function shouldSendRecoveryAlert(): bool
    {
        return !$this->isDown() && $this->downNotifiedAt() !== null;
    }
}
