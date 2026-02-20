<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\OrganizationCostume;
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
        $trooper_costumes = $message->trooper->trooper_costumes()
            ->whereHas('organization_costume', function ($query) use ($message)
            {
                $query->where(OrganizationCostume::COSTUME_ID, $message->costume_id);
            })
            ->get();

        foreach ($trooper_costumes as $trooper_costume)
        {
            $trooper_costume->delete();
        }

        return null;
    }
}

