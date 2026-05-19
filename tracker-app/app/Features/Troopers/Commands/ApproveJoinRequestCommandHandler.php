<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Notifications\Troopers\JoinRequestApprovedNotification;

/**
 * Handler for approving a club join request.
 *
 * @implements CommandHandlerInterface<ApproveJoinRequestCommand>
 */
readonly class ApproveJoinRequestCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  ApproveJoinRequestCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper_org = $message->trooper_organization;

        // Mark the pending record ACTIVE so the card can display the approved state.
        $trooper_org->membership_status = MembershipStatus::ACTIVE;
        $trooper_org->save();

        $primary_club = $trooper_org->organization->getPrimaryClub();

        // Clear any existing club membership in the same top-level org hierarchy (replace rule).
        TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_org->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereHas('organization', fn ($q) => $q
                ->whereRaw('node_path LIKE ?', [$primary_club->node_path.'%'])
                ->where('id', '!=', $trooper_org->organization_id)
            )
            ->update([TrooperAssignment::IS_MEMBER => false]);

        TrooperAssignment::updateOrCreate(
            [
                TrooperAssignment::TROOPER_ID => $trooper_org->trooper_id,
                TrooperAssignment::ORGANIZATION_ID => $trooper_org->organization_id,
            ],
            [
                TrooperAssignment::IS_MEMBER => true,
            ]
        );

        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($trooper_org->identifier))
        {
            $update_data[TrooperOrganization::IDENTIFIER] = $trooper_org->identifier;
        }

        TrooperOrganization::updateOrCreate(
            [
                TrooperOrganization::TROOPER_ID => $trooper_org->trooper_id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ],
            $update_data
        );

        if (!$message->suppress_notification)
        {
            $trooper_org->trooper->notify(new JoinRequestApprovedNotification($trooper_org->organization));
        }

        return null;
    }
}
