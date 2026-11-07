<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * An officer takes back a costume approval, for example after a trooper sold the costume.
 * Future sign-ups can't use it, past troops keep their credit, and adding it again goes
 * back to an officer for review. Club syncs don't bring it back, even when the club's
 * records still list it, until an officer undoes the revoke or approves it again.
 *
 * @method static void call(Actor $actor, TrooperCostume $trooper_costume)
 */
final class RevokeTrooperCostume extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly Actor $actor,
        private readonly TrooperCostume $trooper_costume,
    ) {}

    public function handle(): void
    {
        /** @var Trooper $officer */
        $officer = $this->actor;

        $this->trooper_costume->markRevoked($officer);
        $this->trooper_costume->save();
        $this->trooper_costume->delete();
    }
}
