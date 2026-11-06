<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Mail\Events\ShareEventRoster as ShareEventRosterMail;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Command to share an event roster with a coordinator.
 *
 * Creates an EventShare record with a shareable token that allows
 * the recipient to view the event roster via email. The share expires
 * one day after the event ends.
 *
 * @method static void call(Event $event, string $recipient_email, Trooper $shared_by_trooper)
 */
final class ShareEventRoster extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event whose roster is being shared.
     * @param  string  $recipient_email  The email address to share the roster with.
     * @param  Trooper  $shared_by_trooper  The trooper who is sharing the roster.
     */
    public function __construct(
        private readonly Event $event,
        private readonly string $recipient_email,
        private readonly Trooper $shared_by_trooper,
    ) {}

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
     * @return null Always returns null
     */
    public function handle(): void
    {
        $share = new EventShare;

        $share->share_token = Str::uuid()->toString();
        $share->event_id = $this->event->id;
        $share->trooper_id = $this->shared_by_trooper->id;
        $share->expires_at = $this->event->event_end->addDay();
        $share->recipient_email = $this->recipient_email;
        $share->save();

        $share->event = $this->event;

        Mail::to($this->recipient_email)
            ->cc($this->shared_by_trooper->email)
            ->queue(new ShareEventRosterMail($share));
    }
}
