<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TrooperRequest;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Command staff approved a trooper's request to join an organization.
 */
final class TrooperRequestApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly TrooperRequest $trooper_request) {}
}
