<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\EventTrooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for event shift completion notification.
 *
 * Sent to troopers after their event shift ends, requesting them to confirm
 * whether they attended or were unable to attend. Includes encrypted status
 * links for one-click confirmation.
 */
class EventShiftComplete extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * Create a new event shift completion email instance.
     *
     * @param  EventTrooper  $event_trooper  The event trooper assignment to notify about
     */
    public function __construct(private readonly EventTrooper $event_trooper)
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
            subject: config('mail.prefix').' Event Shift Complete - Action Required'
        );
    }

    /**
     * Get the message content definition with event shift data.
     *
     * Provides the event trooper, trooper, event shift, event, and encrypted
     * status links to the email template.
     *
     * @return Content The email content configuration with view and data
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.events.event-shift-complete',
            with: [
                'event_trooper' => $this->event_trooper,
                'trooper' => $this->event_trooper->trooper,
                'event_shift' => $this->event_trooper->event_shift,
                'event' => $this->event_trooper->event_shift->event,
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
