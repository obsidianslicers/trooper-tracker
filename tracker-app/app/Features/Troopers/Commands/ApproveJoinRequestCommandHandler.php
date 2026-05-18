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

        TrooperAssignment::updateOrCreate(
            [
                TrooperAssignment::TROOPER_ID      => $request->trooper_id,
                TrooperAssignment::ORGANIZATION_ID => $request->organization_id,
            ],
            [
                TrooperAssignment::IS_MEMBER => true,
            ]
        );

        $primary_club = $request->organization->getPrimaryClub();

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
