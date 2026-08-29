<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\EventOrganization;

/**
 * Handler for granting the hosting organization's primary club attendance.
 *
 * Resolves the costume club at the root of the event's hosting organization
 * and upserts its EventOrganization pivot with can_attend enabled, so the
 * host's members remain eligible to sign up after creation or reassignment.
 *
 * @implements CommandHandlerInterface<GrantHostClubAttendanceCommand>
 */
readonly class GrantHostClubAttendanceCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to grant the host club attendance.
     *
     * @param  GrantHostClubAttendanceCommand  $message  The command with the event
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $costume_club = $message->event->organization()->first()?->getPrimaryClub();

        if ($costume_club === null)
        {
            return null;
        }

        EventOrganization::updateOrCreate(
            [
                EventOrganization::EVENT_ID => $message->event->id,
                EventOrganization::ORGANIZATION_ID => $costume_club->id,
            ],
            [
                EventOrganization::CAN_ATTEND => true,
            ]
        );

        return null;
    }
}
