<?php

namespace App\Mail\Events;

use App\Models\Event;
use App\Models\EventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
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

    /**
     * Create a new cancelled event notification email instance.
     *
     * @param Event $event The event that was cancelled
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
            subject: 'Troop Tracker - Event Cancelled'
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
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Callback executed after the email is successfully sent.
     *
     * Updates the event notification's sent_at timestamp to track when
     * the notification was delivered to the recipient.
     *
     * @param \Symfony\Component\Mime\Email $message The sent email message instance.
     * @return void
     */
    public function sent($message): void
    {
        $this->event_notification->sent_at = now();
        $this->event_notification->save();
    }
}
