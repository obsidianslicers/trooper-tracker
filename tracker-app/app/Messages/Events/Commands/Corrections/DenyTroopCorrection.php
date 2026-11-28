<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Corrections;

use App\Models\TroopCorrection;
use App\Models\Trooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use RuntimeException;

/**
 * The event coordinator doesn't remember the trooper at their event. That isn't the last
 * word: moderators look at it next, and see that the coordinator said no.
 *
 * @method static void call(TroopCorrection $troop_correction, Trooper $coordinator)
 */
final class DenyTroopCorrection extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly TroopCorrection $troop_correction,
        private readonly Trooper $coordinator,
    ) {}

    public function handle(): void
    {
        $correction = $this->troop_correction;

        if (!$correction->isAwaitingCoordinatorFrom($this->coordinator))
        {
            throw new RuntimeException('This troop credit request is not waiting for this coordinator.');
        }

        $correction->coordinator_denied_at = now();

        EscalateTroopCorrection::call(troop_correction: $correction);
    }
}
