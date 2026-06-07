<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\EventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

/**
 * Mailable for instant event notification.
 *
 * Sent immediately to troopers when a new event is posted, based on their
 * instant notification preferences. Tracks when the notification was sent
 * via the sent() callback.
 */
class InstantEventNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use HasRetryPolicy;

    /**
     * Create a new instant event notification email instance.
     *
     * @param  EventNotification  $event_notification  The notification record to send
     */
    public function __construct(private readonly EventNotification $event_notification)
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
            subject: config('mail.prefix') . ' New Event Posted'
        );
    }

    /**
     * Get the message content definition with event notification data.
     *
     * @return Content The email content configuration with view and event data
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.events.instant-event-notification',
            with: [
                'event_notification' => $this->event_notification,
                'event_shifts' => $this->event_notification->event->event_shifts,
                'event' => $this->event_notification->event,
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

    /**
     * Callback executed after the email is successfully sent.
     *
     * Updates the event notification's sent_at timestamp to track when
     * the notification was delivered to the recipient.
     *
     * @param  Email  $message  The sent email message instance
     */
    public function sent($message): void
    {
        $this->event_notification->sent_at = now();
        $this->event_notification->save();
    }
}
