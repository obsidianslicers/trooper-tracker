<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handler for updating an event trooper's sign-up information.
 *
 * Updates an existing EventTrooper record with validated data and persists
 * the changes to the database.
 *
 * @implements CommandHandlerInterface<UpdateEventTrooperCommand>
 */
readonly class UpdateEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update event trooper information.
     *
     * @param  UpdateEventTrooperCommand  $message  The command with event trooper and update data.
     * @return null Always returns null.
     */
    public function __invoke(object $message): mixed
    {
        $message->event_trooper->fill($message->valid_data);

        $message->event_trooper->save();

        return null;
    }
}
