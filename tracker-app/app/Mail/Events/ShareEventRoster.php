<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Models\EventShare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for sharing event roster with external recipients.
 *
 * Sent to coordinators or external contacts with a shareable link to view
 * an event roster without authentication. The share includes a unique token
 * and expiration details.
 */
class ShareEventRoster extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 60];
    }

    /**
     * Create a new event roster share email instance.
     *
     * @param  EventShare  $event_share  The event share record containing the shareable link
     */
    public function __construct(private readonly EventShare $event_share)
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
            subject: config('mail.prefix').' Event Roster Link'
        );
    }

    /**
     * Get the message content definition with shareable roster link.
     *
     * Provides the event share, trooper who shared it, and event details
     * to the email template.
     *
     * @return Content The email content configuration with view and data
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.events.share-event-roster',
            with: [
                'event_share' => $this->event_share,
                'trooper' => $this->event_share->trooper,
                'event' => $this->event_share->event,
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
