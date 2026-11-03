<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AwardTrooper;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An award was given to a trooper.
 */
final class AwardGranted implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly AwardTrooper $award_trooper) {}
}
