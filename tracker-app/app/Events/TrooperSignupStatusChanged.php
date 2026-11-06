<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A trooper's status on an event shift changed after they signed up (for example going to
 * cancelled, or stand-by to going).
 */
final class TrooperSignupStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly EventTrooper $signup,
        public readonly ?EventTrooperStatus $previous_status,
    ) {}
}
