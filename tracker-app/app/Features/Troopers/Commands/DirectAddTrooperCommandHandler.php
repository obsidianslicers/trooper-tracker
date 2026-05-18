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

        if (!empty($message->identifier))
        {
            TrooperOrganization::updateOrCreate(
                [
                    TrooperOrganization::TROOPER_ID      => $message->trooper->id,
                    TrooperOrganization::ORGANIZATION_ID => $message->organization->id,
                ],
                [
                    TrooperOrganization::IDENTIFIER        => $message->identifier,
                    TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
                ]
            );
        }

        $message->trooper->notify(new DirectlyAddedToClubNotification($message->organization));

        return null;
    }
}
