<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\JoinRequestApprovedNotification;

/**
 * Handler for approving a club join request.
 *
 * @implements CommandHandlerInterface<ApproveTrooperRequestCommand>
 */
readonly class ApproveTrooperRequestCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  ApproveTrooperRequestCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper_request = $message->trooper_request;
        $trooper = $trooper_request->trooper;
        $primary_club = $trooper_request->primaryOrganization;
        $requested_org = $trooper_request->organization;

        $this->clearExistingAssignments($primary_club, $trooper_request);
        $this->createOrUpdateMembership($primary_club, $trooper_request);
        $this->createOrUpdateAssignment($requested_org->id, $trooper->id);
        $this->createOrUpdateNotificationAssignments($primary_club, $requested_org, $trooper->id);

        $trooper_request->status = TrooperRequestStatus::APPROVED;
        $trooper_request->approved_at = now();
        $trooper_request->save();

        if (!$message->suppress_notification)
        {
            $trooper->notify(new JoinRequestApprovedNotification($trooper_request));
        }

        return null;
    }

    private function clearExistingAssignments(Organization $primary_club, TrooperRequest $trooper_request): void
    {
        $ids = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_request->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->where(TrooperAssignment::ORGANIZATION_ID, '!=', $trooper_request->organization_id)
            ->whereHas('organization', function ($q) use ($primary_club): void {
                $q->where(Organization::NODE_PATH, 'like', $primary_club->node_path.'%')
                    ->orWhereRaw('? LIKE CONCAT('.Organization::NODE_PATH.', "%")', [$primary_club->node_path]);
            })
            ->pluck(TrooperAssignment::ID);

        if ($ids->isEmpty())
        {
            return;
        }

        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)->update([TrooperAssignment::IS_MEMBER => false]);
        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)->delete();
    }

    private function createOrUpdateMembership(Organization $primary_club, TrooperRequest $trooper_request): void
    {
        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($trooper_request->identifier))
        {
            $update_data[TrooperOrganization::IDENTIFIER] = $trooper_request->identifier;
        }

        $record = TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $trooper_request->trooper_id)
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
                TrooperOrganization::TROOPER_ID => $trooper_request->trooper_id,
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
            TrooperAssignment::TROOPER_ID => $trooper_id,
            TrooperAssignment::ORGANIZATION_ID => $organization_id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    private function createOrUpdateNotificationAssignments(Organization $primary_club, Organization $requested_org, int $trooper_id): void
    {
        foreach ($this->notificationOrganizations($primary_club, $requested_org) as $organization)
        {
            $this->createOrUpdateNotificationAssignment($organization->id, $trooper_id);
        }
    }

    /**
     * @return array<int, Organization>
     */
    private function notificationOrganizations(Organization $primary_club, Organization $requested_org): array
    {
        $organizations = [];
        $organization = $requested_org;

        while ($organization !== null)
        {
            $organizations[$organization->id] = $organization;

            if ($organization->id === $primary_club->id)
            {
                break;
            }

            $organization = $organization->parent;
        }

        $organizations[$primary_club->id] = $primary_club;

        return $organizations;
    }

    private function createOrUpdateNotificationAssignment(int $organization_id, int $trooper_id): void
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

            $assignment->should_notify = true;
            $assignment->save();

            return;
        }

        TrooperAssignment::create([
            TrooperAssignment::TROOPER_ID => $trooper_id,
            TrooperAssignment::ORGANIZATION_ID => $organization_id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }
}
