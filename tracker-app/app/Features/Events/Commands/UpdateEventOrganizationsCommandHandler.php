<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\EventOrganization;

/**
 * Handler for updating event organization attendance settings.
 *
 * Synchronizes the event's organization relationships with attendance
 * permissions and limits. Organizations not in the data array are set
 * to can_attend=false with null limits. Provided organizations are
 * updated with their specified settings.
 *
 * @implements CommandHandlerInterface<UpdateEventOrganizationsCommand>
 */
readonly class UpdateEventOrganizationsCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update event organization settings.
     *
     * @param  UpdateEventOrganizationsCommand  $message  The command with event and organization data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $pivot_data = $message->event->organizations()
            ->get()
            ->mapWithKeys(fn ($org) => [
                $org->id => [
                    EventOrganization::CAN_ATTEND => false,
                    EventOrganization::TROOPERS_ALLOWED => null,
                    EventOrganization::HANDLERS_ALLOWED => null,
                ],
            ])
            ->toArray();

        //  merge arrays - left wins
        $updates = array_replace_recursive($pivot_data, $message->data);

        $message->event->organizations()->syncWithoutDetaching($updates);

        return null;
    }
}
