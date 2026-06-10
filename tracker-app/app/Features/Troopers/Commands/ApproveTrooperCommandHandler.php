<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Bus\MagicBus;
use App\Enums\MembershipStatus;
use App\Models\JoinRequest;
use App\Notifications\Troopers\MembershipApprovedNotification;
use App\Notifications\Troopers\TrooperDeniedNotification;

/**
 * Handler for approving a trooper's membership application.
 *
 * @implements CommandHandlerInterface<ApproveTrooperCommand>
 */
readonly class ApproveTrooperCommandHandler implements CommandHandlerInterface
{
    public function __construct(private MagicBus $bus) {}

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

        if (!$message->is_approved)
        {
            $message->trooper->notify(new TrooperDeniedNotification($message->denial_reason));
        }

        if ($message->is_approved)
        {
            $message->trooper->notify(new MembershipApprovedNotification);

            JoinRequest::where(JoinRequest::TROOPER_ID, $message->trooper->id)
                ->pending()
                ->get()
                ->each(function (JoinRequest $join_request): void {
                    $this->bus->send(new ApproveJoinRequestCommand($join_request, suppress_notification: true));
                });
        }

        return null;
    }
}
