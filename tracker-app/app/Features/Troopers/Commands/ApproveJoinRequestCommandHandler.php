<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Models\JoinRequest;
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
        $join_request  = $message->join_request;
        $trooper       = $join_request->trooper;
        $primary_club  = $join_request->primaryOrganization;
        $requested_org = $join_request->organization;

        $this->clearExistingAssignments($primary_club, $join_request);
        $this->createOrUpdateMembership($primary_club, $join_request);
        $this->createOrUpdateAssignment($requested_org->id, $trooper->id);

        $join_request->status      = JoinRequestStatus::APPROVED;
        $join_request->approved_at = now();
        $join_request->save();

        if (!$message->suppress_notification)
        {
            $trooper->notify(new JoinRequestApprovedNotification($join_request));
        }

        return null;
    }

    private function clearExistingAssignments(Organization $primary_club, JoinRequest $join_request): void
    {
        $ids = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $join_request->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->where(TrooperAssignment::ORGANIZATION_ID, '!=', $join_request->organization_id)
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

    private function createOrUpdateMembership(Organization $primary_club, JoinRequest $join_request): void
    {
        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($join_request->identifier))
        {
            $update_data[TrooperOrganization::IDENTIFIER] = $join_request->identifier;
        }

        $record = TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $join_request->trooper_id)
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
                TrooperOrganization::TROOPER_ID      => $join_request->trooper_id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ],
            $update_data
        ));
    }

    private function createOrUpdateAssignment(int $organization_id, int $trooper_id): void
    {
        $assignment = TrooperAssignment::withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $trooper_id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id)
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
            TrooperAssignment::TROOPER_ID      => $trooper_id,
            TrooperAssignment::ORGANIZATION_ID => $organization_id,
            TrooperAssignment::IS_MEMBER       => true,
        ]);
    }
}
