<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\EventTrooper;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A trooper signed up for an event shift, including signing back up after being removed.
 *
 * $signup->status says whether they are going, on stand-by, or pending a costume.
 */
final class TrooperSignedUp implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly EventTrooper $signup) {}
}
