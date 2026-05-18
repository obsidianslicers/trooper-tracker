<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Notifications\Troopers\DirectlyAddedToClubNotification;

/**
 * Handler for directly adding a trooper to an organization roster.
 *
 * @implements CommandHandlerInterface<DirectAddTrooperCommand>
 */
readonly class DirectAddTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  DirectAddTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        TrooperAssignment::updateOrCreate(
            [
                TrooperAssignment::TROOPER_ID      => $message->trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $message->organization->id,
            ],
            [
                TrooperAssignment::IS_MEMBER => true,
            ]
        );

        $primary_club = $message->organization->getPrimaryClub();

        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($message->identifier))
        {
            $update_data[TrooperOrganization::IDENTIFIER] = $message->identifier;
        }

        TrooperOrganization::updateOrCreate(
            [
                TrooperOrganization::TROOPER_ID      => $message->trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ],
            $update_data
        );

        $message->trooper->notify(new DirectlyAddedToClubNotification($message->organization));

        return null;
    }
}
