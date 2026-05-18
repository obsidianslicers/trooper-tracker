<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\JoinRequestStatus;
use App\Jobs\SendJoinRequestNotificationsJob;
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
        $join_request = TrooperJoinRequest::updateOrCreate(
            [
                TrooperJoinRequest::TROOPER_ID      => $message->trooper->id,
                TrooperJoinRequest::ORGANIZATION_ID => $message->organization->id,
            ],
            [
                TrooperJoinRequest::STATUS     => JoinRequestStatus::PENDING,
                TrooperJoinRequest::IDENTIFIER => $message->identifier,
            ]
        );

        SendJoinRequestNotificationsJob::dispatch($join_request);

        return null;
    }
}
