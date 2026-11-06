<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Models\TrooperCostume;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * An officer takes back a costume approval, for example after a trooper sold the costume.
 * Future sign-ups can't use it, past troops keep their credit, and adding it again goes
 * back to an officer for review.
 */
final class RevokeTrooperCostume extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly TrooperCostume $trooper_costume,
    ) {}

    public function handle(): void
    {
        $this->trooper_costume->markPending();
        $this->trooper_costume->save();
        $this->trooper_costume->delete();
    }
}
