<?php

namespace App\Mail\Events;

use App\Models\EventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DailyEventNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private readonly Collection $event_notifications)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Troop Tracker - New Event Posted'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->event_notifications->load(['event', 'event.organization', 'event.event_shifts',]);

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
        foreach ($this->event_notifications as $event_notification)
        {
            $event_notification->sent_at = now();
            $event_notification->save();
        }
    }
}
