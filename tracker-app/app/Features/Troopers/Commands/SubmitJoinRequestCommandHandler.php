<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\JoinRequestStatus;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\Organization;
use App\Models\TrooperJoinRequest;

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

        TrooperJoinRequest::where(TrooperJoinRequest::TROOPER_ID, $trooper->id)
            ->where(TrooperJoinRequest::STATUS, JoinRequestStatus::PENDING)
            ->whereIn(TrooperJoinRequest::ORGANIZATION_ID, $family_ids)
            ->where(TrooperJoinRequest::ORGANIZATION_ID, '!=', $organization->id)
            ->delete();

        $join_request = TrooperJoinRequest::updateOrCreate(
            [
                TrooperJoinRequest::TROOPER_ID      => $trooper->id,
                TrooperJoinRequest::ORGANIZATION_ID => $organization->id,
            ],
            [
                TrooperJoinRequest::STATUS     => JoinRequestStatus::PENDING,
                TrooperJoinRequest::IDENTIFIER => $message->identifier,
                TrooperJoinRequest::UPDATED_AT => now(),
            ]
        );

        SendJoinRequestNotificationsJob::dispatch($join_request);

        return null;
    }
}
