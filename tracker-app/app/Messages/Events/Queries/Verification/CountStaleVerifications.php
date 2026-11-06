<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries\Verification;

use App\Models\EventTrooper;
use Hyperdrive\Message;

/**
 * Troops whose credit has waited for verification longer than the given number of days.
 */
final class CountStaleVerifications extends Message
{
    public function __construct(
        private readonly int $days,
    ) {}

    public function handle(): int
    {
        return EventTrooper::query()
            ->whereNotNull(EventTrooper::REQUESTED_ORGANIZATION_IDS)
            ->where(EventTrooper::VERIFICATION_REQUESTED_AT, '<=', now()->subDays($this->days))
            ->whereHas('trooper')
            ->count();
    }
}
