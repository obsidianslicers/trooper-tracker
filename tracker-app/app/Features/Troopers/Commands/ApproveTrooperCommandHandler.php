<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Notifications\Troopers\MembershipApprovedNotification;

/**
 * Handler for approving a trooper's membership application.
 *
 * @implements CommandHandlerInterface<ApproveTrooperCommand>
 */
readonly class ApproveTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  ApproveTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $message->trooper->membership_status = $message->is_approved
            ? MembershipStatus::ACTIVE
            : MembershipStatus::DENIED;

        if ($message->is_approved && $message->trooper->is_visitor)
        {
            $message->trooper->visitor_expires_at = now()->addMonths(6);
            $message->trooper->visitor_notified_at = null;
        }

        $message->trooper->save();

        if ($message->is_approved)
        {
            $message->trooper->notify(new MembershipApprovedNotification);
        }

        return null;
    }
}
