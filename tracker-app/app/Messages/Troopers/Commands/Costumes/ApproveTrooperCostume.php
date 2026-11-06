<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use RuntimeException;

/**
 * An officer approves a costume a trooper added for their club, so it can be used for
 * sign-ups and credit from now on.
 */
final class ApproveTrooperCostume extends Message
{
    public function __construct(
        private readonly Actor $actor,
        private readonly TrooperCostume $trooper_costume,
    ) {}

    public function handle(): void
    {
        if (!$this->trooper_costume->isPending())
        {
            throw new RuntimeException('Trooper costume is not pending.');
        }

        /** @var Trooper $approver */
        $approver = $this->actor;

        $this->trooper_costume->markApproved($approver);
        $this->trooper_costume->save();
    }
}
