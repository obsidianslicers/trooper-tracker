<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Models\TrooperAssignment;
use App\Models\TrooperJoinRequest;
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
        $request = $message->join_request;

        $request->status = JoinRequestStatus::APPROVED;
        $request->save();

        $primary_club = $request->organization->getPrimaryClub();

        // Clear any existing club membership in the same top-level org hierarchy (replace rule).
        TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $request->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereHas('organization', fn ($q) => $q
                ->whereRaw('node_path LIKE ?', [$primary_club->node_path . '%'])
                ->where('id', '!=', $request->organization_id)
            )
            ->update([TrooperAssignment::IS_MEMBER => false]);

        TrooperAssignment::updateOrCreate(
            [
                TrooperAssignment::TROOPER_ID      => $request->trooper_id,
                TrooperAssignment::ORGANIZATION_ID => $request->organization_id,
            ],
            [
                TrooperAssignment::IS_MEMBER => true,
            ]
        );

        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($request->identifier))
        {
            $update_data[TrooperOrganization::IDENTIFIER] = $request->identifier;
        }

        TrooperOrganization::updateOrCreate(
            [
                TrooperOrganization::TROOPER_ID      => $request->trooper_id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ],
            $update_data
        );

        $request->trooper->notify(new JoinRequestApprovedNotification($request->organization));

        return null;
    }
}
