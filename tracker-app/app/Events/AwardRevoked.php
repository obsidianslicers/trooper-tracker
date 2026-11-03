<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AwardTrooper;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An award was taken back from a trooper. The AwardTrooper row is already soft deleted.
 */
final class AwardRevoked implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly AwardTrooper $award_trooper) {}
}
