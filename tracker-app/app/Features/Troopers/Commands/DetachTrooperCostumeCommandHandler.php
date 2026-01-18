<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\TrooperCostume;

/**
 * Handler for detaching a costume from a trooper.
 *
 * Soft-deletes the TrooperCostume record, allowing it to be restored later.
 * If the costume is not found, the operation silently succeeds (idempotent).
 *
 * @implements CommandHandlerInterface<DetachTrooperCostumeCommand>
 */
readonly class DetachTrooperCostumeCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to detach a costume.
     *
     * @param DetachTrooperCostumeCommand $message The command with trooper and costume ID
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        /** @var DetachTrooperCostumeCommand $message */

        $trooper_costume = $message->trooper->trooper_costumes()
            ->where(TrooperCostume::ID, $message->costume_id)
            ->first();

        if ($trooper_costume !== null)
        {
            $trooper_costume->delete();
        }

        return null;
    }
}

