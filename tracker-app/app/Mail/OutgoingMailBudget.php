<?php

declare(strict_types=1);

namespace App\Mail;

use DateTimeInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps outgoing mail under a host's cap on recipients per hour (DreamHost allows 100
 * per sending address and blocks the address when it goes over). Every message sent,
 * queued or not, is counted over a rolling hour; a fixed hourly window would let
 * twice the cap through across the boundary between two windows.
 *
 * Off unless mail.recipients_per_hour (MAIL_RECIPIENTS_PER_HOUR) is set.
 */
final class OutgoingMailBudget
{
    private const CACHE_KEY = 'mail:recipients-sent';

    private const WINDOW_SECONDS = 3600;

    /**
     * How long a held message keeps waiting for room before it fails.
     */
    private const HOLD_HOURS = 24;

    public function enabled(): bool
    {
        return $this->limit() > 0;
    }

    /**
     * Seconds until $recipients more can be sent; 0 means now.
     */
    public function secondsUntilAvailable(int $recipients): int
    {
        $limit = $this->limit();

        // a message bigger than the whole budget would wait forever; let it go
        if ($limit <= 0 || $recipients > $limit)
        {
            return 0;
        }

        $now = now()->getTimestamp();
        $sent = $this->sentSince($now - self::WINDOW_SECONDS);
        $over = array_sum(array_column($sent, 1)) + $recipients - $limit;

        if ($over <= 0)
        {
            return 0;
        }

        foreach ($sent as [$at, $count])
        {
            $over -= $count;

            if ($over <= 0)
            {
                return max(1, $at + self::WINDOW_SECONDS - $now);
            }
        }

        return self::WINDOW_SECONDS;
    }

    public function record(int $recipients): void
    {
        if (!$this->enabled() || $recipients < 1)
        {
            return;
        }

        $write = function () use ($recipients): void
        {
            $now = now()->getTimestamp();
            $sent = $this->sentSince($now - self::WINDOW_SECONDS);
            $sent[] = [$now, $recipients];

            Cache::put(self::CACHE_KEY, $sent, self::WINDOW_SECONDS);
        };

        try
        {
            Cache::lock(self::CACHE_KEY.':lock', 10)->block(5, $write);
        }
        catch (LockTimeoutException)
        {
            // counting matters more than the lock, and a send must never fail over it
            $write();
        }
    }

    /**
     * A release while the budget is spent counts as a try, so held mail retries by
     * time instead of by count. Null leaves the job's own tries in charge.
     */
    public function retryUntil(): ?DateTimeInterface
    {
        return $this->enabled() ? now()->addHours(self::HOLD_HOURS) : null;
    }

    /**
     * @return list<array{0: int, 1: int}> [sent at, recipients], oldest first
     */
    private function sentSince(int $since): array
    {
        return array_values(array_filter(
            Cache::get(self::CACHE_KEY, []),
            fn (array $entry): bool => $entry[0] > $since
        ));
    }

    private function limit(): int
    {
        return (int) config('mail.recipients_per_hour');
    }
}
