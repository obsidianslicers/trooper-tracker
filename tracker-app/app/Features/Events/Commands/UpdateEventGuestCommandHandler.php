<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handles updating an event guest's sign-up information.
 *
 * Fills an EventGuest record with validated data and persists the changes
 * to the database.
 *
 * @implements CommandHandlerInterface<UpdateEventGuestCommand>
 */
readonly class UpdateEventGuestCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update event guest information.
     *
     * @param  UpdateEventGuestCommand  $message  The command with event guest and update data.
     * @return null Always returns null.
     */
    public function __invoke(object $message): mixed
    {
        $message->event_guest->fill($message->valid_data);

        $message->event_guest->save();

        return null;
    }
}
