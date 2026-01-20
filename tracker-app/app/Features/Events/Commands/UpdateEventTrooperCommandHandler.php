<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handler for updating trooper profile information.
 *
 * Fills the trooper model with validated data and saves.
 * If complete_setup flag is true, sets the setup_completed_at timestamp
 * to mark the trooper's initial profile setup as complete.
 *
 * @implements CommandHandlerInterface<UpdateEventTrooperCommand>
 */
readonly class UpdateEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update trooper profile.
     *
     * @param UpdateEventTrooperCommand $message The command with trooper and update data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        /** @var UpdateEventTrooperCommand $message */
        $message->event_trooper->fill($message->valid_data);

        $message->event_trooper->save();

        return null;
    }
}

