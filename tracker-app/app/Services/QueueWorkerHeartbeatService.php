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
}
