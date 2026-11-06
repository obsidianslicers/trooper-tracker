<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Trooper;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Command staff approved a trooper's membership, making them active.
 */
final class TrooperMembershipApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Trooper $trooper) {}
}
