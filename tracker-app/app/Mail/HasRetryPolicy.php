<?php

declare(strict_types=1);

namespace App\Mail;

use DateTimeInterface;

trait HasRetryPolicy
{
    public int $tries = 3;

    /**
     * Still caps real failures once retryUntil() takes over from $tries.
     */
    public int $maxExceptions = 3;

    public function backoff(): array
    {
        return [30, 60];
    }

    public function retryUntil(): ?DateTimeInterface
    {
        return app(OutgoingMailBudget::class)->retryUntil();
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new ThrottleOutgoingMail];
    }
}
