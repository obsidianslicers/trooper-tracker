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

        $this->enforceAssignment($primary_club, $trooper_org);

        if (!$message->suppress_notification)
        {
            $trooper->notify(new JoinRequestApprovedNotification($trooper_org->organization));
        }

        return null;
    }

    private function enforceAssignment(Organization $primary_club, TrooperOrganization $trooper_org): void
    {
        //$this->clearExistingAssignments($primary_club, $trooper_org);
        //$this->createOrUpdateAssignment($trooper_org);
        //$this->syncPrimaryClubMembership($primary_club, $trooper_org);
    }

    private function clearExistingAssignments(Organization $primary_club, TrooperOrganization $trooper_org): void
    {
        $ids = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_org->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->where(TrooperAssignment::ORGANIZATION_ID, '!=', $trooper_org->organization_id)
            ->whereHas('organization', function ($q) use ($primary_club): void
            {
                $q->where(Organization::NODE_PATH, 'like', $primary_club->node_path . '%')
                    ->orWhereRaw('? LIKE CONCAT(' . Organization::NODE_PATH . ', "%")', [$primary_club->node_path]);
            })
            ->pluck(TrooperAssignment::ID);

        if ($ids->isEmpty())
        {
            return;
        }

        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)->update([TrooperAssignment::IS_MEMBER => false]);
        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)->delete();
    }

    private function createOrUpdateAssignment(TrooperOrganization $trooper_org): void
    {
        $assignment = TrooperAssignment::withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $trooper_org->trooper_id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $trooper_org->organization_id)
            ->first();

        if ($assignment)
        {
            if ($assignment->trashed())
            {
                $assignment->restore();
            }

            $assignment->is_member = true;
            $assignment->save();

            return;
        }

        TrooperAssignment::create([
            TrooperAssignment::TROOPER_ID => $trooper_org->trooper_id,
            TrooperAssignment::ORGANIZATION_ID => $trooper_org->organization_id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    private function syncPrimaryClubMembership(Organization $primary_club, TrooperOrganization $trooper_org): void
    {
        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($trooper_org->identifier))
        {
            $update_data[TrooperOrganization::IDENTIFIER] = $trooper_org->identifier;
        }

        $record = TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $trooper_org->trooper_id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $primary_club->id)
            ->first();

        if ($record)
        {
            if ($record->trashed())
            {
                $record->restore();
            }

            $record->fill($update_data)->save();

            return;
        }

        TrooperOrganization::create(array_merge(
            [
                TrooperOrganization::TROOPER_ID => $trooper_org->trooper_id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ],
            $update_data
        ));
    }
}
