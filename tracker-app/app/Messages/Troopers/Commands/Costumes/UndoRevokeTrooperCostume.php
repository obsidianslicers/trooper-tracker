<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use RuntimeException;

/**
 * An officer takes back a revoke: the costume is approved again and club syncs
 * manage it as usual.
 *
 * @method static void call(Actor $actor, TrooperCostume $trooper_costume)
 */
final class UndoRevokeTrooperCostume extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly Actor $actor,
        private readonly TrooperCostume $trooper_costume,
    ) {}

    public function handle(): void
    {
        if (!$this->trooper_costume->isRevoked())
        {
            throw new RuntimeException('Trooper costume is not revoked.');
        }

        /** @var Trooper $officer */
        $officer = $this->actor;

        if ($this->trooper_costume->trashed())
        {
            $this->trooper_costume->restore();
        }

        $this->trooper_costume->markApproved($officer);
        $this->trooper_costume->save();
    }
}
