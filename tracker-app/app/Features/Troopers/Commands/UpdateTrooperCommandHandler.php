<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handler for updating trooper profile information.
 *
 * Fills the trooper model with validated data and saves.
 * If complete_setup flag is true, sets the setup_completed_at timestamp
 * to mark the trooper's initial profile setup as complete.
 *
 * @implements CommandHandlerInterface<UpdateTrooperCommand>
 */
readonly class UpdateTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update trooper profile.
     *
     * @param UpdateTrooperCommand $message The command with trooper and update data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        /** @var UpdateTrooperCommand $message */
        $message->trooper->fill($message->valid_data);

        if ($message->complete_setup)
        {
            $message->trooper->setup_completed_at = now();
        }

        $message->trooper->save();

        return null;
    }
}

