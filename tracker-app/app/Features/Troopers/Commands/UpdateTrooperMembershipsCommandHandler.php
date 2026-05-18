<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Models\TrooperOrganization;

/**
 * Handler for updating trooper organization memberships.
 *
 * Creates a PENDING TrooperOrganization record for each selected organization.
 * Membership is not granted until an admin approves the request via the approvals page.
 *
 * @implements CommandHandlerInterface<UpdateTrooperMembershipsCommand>
 */
readonly class UpdateTrooperMembershipsCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  UpdateTrooperMembershipsCommand  $message
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        foreach ($message->valid_data as $organization_id => $data)
        {
            $assignment_id = $data['assignment'] ?? null;

            if (!$assignment_id)
            {
                continue;
            }

            TrooperOrganization::firstOrCreate(
                [
                    TrooperOrganization::TROOPER_ID      => $message->trooper->id,
                    TrooperOrganization::ORGANIZATION_ID => $assignment_id,
                ],
                [
                    TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
                ]
            );
        }

        return null;
    }
}
