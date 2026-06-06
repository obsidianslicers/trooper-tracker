<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for cancelled event notification.
 *
 * Sent to troopers who were signed up for an event when it gets cancelled,
 * informing them of the cancellation.
 */
class CancelledEventNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 60];
    }

    /**
     * Create a new cancelled event notification email instance.
     *
     * @param  Event  $event  The event that was cancelled
     */
    public function __construct(private readonly Event $event)
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
            subject: config('mail.prefix').' Event Cancelled'
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
            view: 'emails.events.cancelled-event-notification',
            with: [
                'event' => $this->event,
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
