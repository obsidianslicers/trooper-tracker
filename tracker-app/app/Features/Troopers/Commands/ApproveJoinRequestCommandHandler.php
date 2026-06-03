<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Models\Organization;
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
        $trooper = $trooper_org->trooper;

        // Mark the pending record ACTIVE so the card can display the approved state.
        $trooper_org->membership_status = MembershipStatus::ACTIVE;
        $trooper_org->save();

        $primary_club = $trooper_org->organization->getPrimaryClub();

        if ($trooper->is_visitor)
        {
            $this->enforceVisitorAssignment($primary_club, $trooper_org);
        }

        if (!$message->suppress_notification)
        {
            $trooper->notify(new JoinRequestApprovedNotification($trooper_org->organization));
        }

        return null;
    }

    private function enforceVisitorAssignment(Organization $primary_club, TrooperOrganization $trooper_org): void
    {
        $this->clearExistingVisitorAssignments($primary_club, $trooper_org);
        $this->createOrUpdateVisitorAssignment($primary_club, $trooper_org);
        $this->updateOrganizationIdentifer($primary_club, $trooper_org);
    }

    private function clearExistingVisitorAssignments(Organization $primary_club, TrooperOrganization $trooper_org): void
    {
        // Clear any existing club membership in the same top-level org hierarchy (replace rule).
        TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_org->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereHas('organization', function ($q) use ($primary_club) {
                return $q->where(Organization::NODE_PATH, 'like', $primary_club->node_path.'%');
            })
            ->update([TrooperAssignment::IS_MEMBER => false]);
    }

    private function createOrUpdateVisitorAssignment(Organization $primary_club, TrooperOrganization $trooper_org): void
    {
        $key = [
            TrooperAssignment::TROOPER_ID => $trooper_org->trooper_id,
            TrooperAssignment::ORGANIZATION_ID => $trooper_org->organization_id,
        ];

        $set = [
            TrooperAssignment::IS_MEMBER => true,
        ];

        TrooperAssignment::updateOrCreate($key, $set);
    }

    private function updateOrganizationIdentifer(Organization $primary_club, TrooperOrganization $trooper_org): void
    {
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
    }
}
