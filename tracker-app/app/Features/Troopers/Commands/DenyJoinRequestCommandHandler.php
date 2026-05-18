<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
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
        $request = $message->trooper_organization;

        $request->membership_status = MembershipStatus::DENIED;
        $request->save();

        $request->trooper->notify(new JoinRequestDeniedNotification($request->organization));

        return null;
    }
}
