<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\JoinRequestStatus;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\JoinRequest;

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
        $organization  = $message->organization;
        $trooper       = $message->trooper;
        $primary_club  = $organization->getPrimaryClub();

        // Cancel any other pending request in this primary-club family.
        JoinRequest::where(JoinRequest::TROOPER_ID, $trooper->id)
            ->where(JoinRequest::STATUS, JoinRequestStatus::PENDING)
            ->where(JoinRequest::PRIMARY_ORGANIZATION_ID, $primary_club->id)
            ->delete();

        $join_request = JoinRequest::create([
            JoinRequest::TROOPER_ID              => $trooper->id,
            JoinRequest::ORGANIZATION_ID         => $organization->id,
            JoinRequest::PRIMARY_ORGANIZATION_ID => $primary_club->id,
            JoinRequest::IDENTIFIER              => $message->identifier ?: null,
            JoinRequest::STATUS                  => JoinRequestStatus::PENDING,
        ]);

        SendJoinRequestNotificationsJob::dispatch($join_request);

        return null;
    }
}
