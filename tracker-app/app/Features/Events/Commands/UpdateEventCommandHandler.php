<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handler for updating event details.
 *
 * Fills the event model with validated data and persists changes to the database.
 * Updates all event properties based on the provided data array.
 *
 * @implements CommandHandlerInterface<UpdateEventCommand>
 */
readonly class UpdateEventCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update the event.
     *
     * @param UpdateEventCommand $message The command with event and update data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $message->event->fill($message->data);

        $message->event->save();

        return null;
    }
}
