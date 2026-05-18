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
        $request = $message->join_request;

        $request->status = JoinRequestStatus::DENIED;
        $request->save();

        $request->trooper->notify(new JoinRequestDeniedNotification($request->organization));

        return null;
    }
}
