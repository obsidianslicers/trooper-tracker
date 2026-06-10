<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\JoinRequestStatus;
use App\Notifications\Troopers\JoinRequestDeniedNotification;

/**
 * Handler for denying a club join request.
 *
 * @implements CommandHandlerInterface<DenyJoinRequestCommand>
 */
readonly class DenyJoinRequestCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  DenyJoinRequestCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $join_request = $message->join_request;

        $join_request->status = JoinRequestStatus::DENIED;
        $join_request->denied_at = now();
        $join_request->denial_reason = $message->denial_reason;
        $join_request->save();

        $join_request->trooper->notify(new JoinRequestDeniedNotification($join_request));

        return null;
    }
}
