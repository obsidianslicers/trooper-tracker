<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Mail\Events\ShareEventRoster;
use App\Models\EventShare;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Handler for sharing an event roster with external recipients.
 *
 * Creates an EventShare record with a unique UUID token, linking the event,
 * the trooper who shared it, and the recipient email. Queues an email
 * notification to the recipient with a link to view the roster.
 *
 * @implements CommandHandlerInterface<ShareEventRosterCommand>
 */
readonly class ShareEventRosterCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to share an event roster.
     *
     * Creates a new EventShare record with:
     * - Unique UUID share token for URL generation
     * - Link to event
     * - Link to trooper who shared the roster
     * - Recipient email address
     * - Expiration date (one day after event ends)
     *
     * Saves the EventShare record and queues a ShareEventRoster email notification
     * to the recipient with CC to the sharing trooper.
     *
     * @param  ShareEventRosterCommand  $message  The command containing event, shared_by_trooper, and recipient_email.
     * @return null Always returns null
     */
    public function __invoke(object $message): mixed
    {
        $share = new EventShare;

        $share->share_token = Str::uuid()->toString();
        $share->event_id = $message->event->id;
        $share->trooper_id = $message->shared_by_trooper->id;
        $share->expires_at = $message->event->event_end->addDay();
        $share->recipient_email = $message->recipient_email;
        $share->save();

        $share->event = $message->event;

        Mail::to($message->recipient_email)
            ->cc($message->shared_by_trooper->email)
            ->queue(new ShareEventRoster($share));

        return null;
    }
}
