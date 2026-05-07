<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Mail\Events\TrooperManualSelectionApproved;
use App\Mail\Events\TrooperManualSelectionStandBy;
use Illuminate\Support\Facades\Mail;

/**
 * Handler for sending manual selection notification emails.
 *
 * Queues the appropriate email to the event trooper based on whether they
 * were approved to GOING or moved back to STAND_BY during a manual selection event.
 *
 * @implements CommandHandlerInterface<SendManualSelectionNotificationCommand>
 */
readonly class SendManualSelectionNotificationCommandHandler implements CommandHandlerInterface
{
    /**
     * Queue the appropriate manual selection notification email.
     *
     * @param  SendManualSelectionNotificationCommand  $message
     * @return null Always returns null.
     */
    public function __invoke(object $message): mixed
    {
        $mailable = $message->is_approved
            ? new TrooperManualSelectionApproved($message->event_trooper, $message->actioned_by)
            : new TrooperManualSelectionStandBy($message->event_trooper, $message->actioned_by);

        Mail::to($message->event_trooper->trooper->email)->queue($mailable);

        return null;
    }
}
