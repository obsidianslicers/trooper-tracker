<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Create a new broadcast event message email instance.
 *
 * @param  Event  $event  The event for which the message is being broadcast
 *                        informing them of the cancellation.
 */
class BroadcastEventMessage extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * Create a new broadcast event message email instance.
     *
     * @param  Event  $event  The event for which the message is being broadcast
     * @param  string  $message  The message to be included in the email
     */
    public function __construct(private readonly Event $event, private readonly string $message)
    {
        //
    }

    /**
     * Get the message envelope with subject line.
     *
     * @return Envelope The email envelope configuration
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Event Message'
        );
    }

    /**
     * Get the message content definition with event data.
     *
     * @return Content The email content configuration with view and event data
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.events.broadcast-event-message',
            with: [
                'event' => $this->event,
                'message' => $this->message,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
