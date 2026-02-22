<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Mail\Admin\Troopers\TrooperApproved;
use Illuminate\Support\Facades\Mail;

/**
 * Handler for approving a trooper's membership application.
 *
 * Changes trooper status from PENDING to ACTIVE, enabling full system access.
 * Creates initial organization assignments based on approval context.
 *
 * @implements CommandHandlerInterface<ApproveTrooperCommand>
 */
readonly class ApproveTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to approve or deny a trooper.
     *
     * Sets the trooper's membership_status to either ACTIVE or DENIED.
     * Note: Trooper model changes are NOT persisted (no save() call).
     * Queues a TrooperApproved email notification.
     *
     * @param  ApproveTrooperCommand  $message  The command with trooper and approval status
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $message->trooper->membership_status = $message->is_approved
            ? MembershipStatus::ACTIVE
            : MembershipStatus::DENIED;

        $message->trooper->save();

        Mail::to($message->trooper->email)->queue(new TrooperApproved($message->trooper));

        return null;
    }
}
