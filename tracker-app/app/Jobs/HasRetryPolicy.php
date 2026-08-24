<?php

declare(strict_types=1);

namespace App\Jobs;

trait HasRetryPolicy
{
    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 60];
    }
}
