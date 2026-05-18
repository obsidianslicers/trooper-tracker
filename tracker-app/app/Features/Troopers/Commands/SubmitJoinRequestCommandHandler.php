<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\Organization;
use App\Models\TrooperOrganization;

/**
 * Handler for submitting a trooper's club join request.
 *
 * @implements CommandHandlerInterface<SubmitJoinRequestCommand>
 */
readonly class SubmitJoinRequestCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  SubmitJoinRequestCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $organization = $message->organization;
        $trooper      = $message->trooper;

        // Cancel any OTHER pending request in this top-level family so only one exists at a time.
        $root_id    = explode(Organization::NODE_PATH_SEP, $organization->node_path)[0];
        $root_path  = $root_id . Organization::NODE_PATH_SEP;
        $family_ids = Organization::where(Organization::NODE_PATH, 'LIKE', $root_path . '%')
            ->pluck(Organization::ID);

        TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::PENDING)
            ->whereIn(TrooperOrganization::ORGANIZATION_ID, $family_ids)
            ->where(TrooperOrganization::ORGANIZATION_ID, '!=', $organization->id)
            ->delete();

        $join_request = TrooperOrganization::updateOrCreate(
            [
                TrooperOrganization::TROOPER_ID      => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $organization->id,
            ],
            [
                TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
                TrooperOrganization::IDENTIFIER        => $message->identifier,
                TrooperOrganization::UPDATED_AT        => now(),
            ]
        );

        SendJoinRequestNotificationsJob::dispatch($join_request);

        return null;
    }
}
