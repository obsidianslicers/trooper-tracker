<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\EventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

/**
 * Mailable for daily event notification digest.
 *
 * Sent to troopers on a daily schedule with all new events posted since
 * their last digest, based on daily notification preferences. Tracks when
 * each notification was sent via the sent() callback.
 */
class DailyEventNotification extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * Create a new daily event notification email instance.
     *
     * @param  Collection<int,EventNotification>  $event_notifications  Collection of EventNotification models to include in digest
     */
    public function __construct(private readonly Collection $event_notifications)
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
            subject: config('mail.prefix').' New Event Posted'
        );
    }

    /**
     * Get the message content definition with event notification data.
     *
     * Eagerly loads related event, organization, and event shift data
     * for the notification collection.
     *
     * @return Content The email content configuration with view and notifications data
     */
    public function content(): Content
    {
        $this->event_notifications->load(['event', 'event.organization', 'event.event_shifts']);

        return new Content(
            view: 'emails.events.daily-event-notification',
            with: [
                'event_notifications' => $this->event_notifications,
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
     * Updates each event notification's sent_at timestamp to track when
     * the notifications were delivered to the recipient.
     *
     * @param  Email  $message  The sent email message instance
     */
    public function sent($message): void
    {
        foreach ($this->event_notifications as $event_notification)
        {
            $event_notification->sent_at = now();
            $event_notification->save();
        }
    }
}
