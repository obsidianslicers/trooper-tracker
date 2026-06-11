<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\TrooperRequestStatus;
use App\Notifications\Troopers\JoinRequestDeniedNotification;

/**
 * Handler for denying a club join request.
 *
 * @implements CommandHandlerInterface<DenyTrooperRequestCommand>
 */
readonly class DenyTrooperRequestCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  DenyTrooperRequestCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper_request = $message->trooper_request;

        $trooper_request->status = TrooperRequestStatus::DENIED;
        $trooper_request->denial_reason = $message->denial_reason;
        $trooper_request->save();

        if (!$message->suppress_notification)
        {
            $trooper_request->trooper->notify(new JoinRequestDeniedNotification($trooper_request));
        }

        return null;
    }
}
