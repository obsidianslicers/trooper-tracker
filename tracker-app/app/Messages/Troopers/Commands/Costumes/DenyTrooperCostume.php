<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Models\TrooperCostume;
use Hyperdrive\Message;
use RuntimeException;

/**
 * An officer turns down a costume a trooper added for their club. The row is removed
 * but stays pending, so adding it again sends it back for review.
 */
final class DenyTrooperCostume extends Message
{
    public function __construct(
        private readonly TrooperCostume $trooper_costume,
    ) {}

    public function handle(): void
    {
        if (!$this->trooper_costume->isPending())
        {
            throw new RuntimeException('Trooper costume is not pending.');
        }

        $this->trooper_costume->delete();
    }
}
