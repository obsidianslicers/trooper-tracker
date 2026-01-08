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

class CancelledEventNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private readonly Event $event)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Troop Tracker - Event Cancelled'
        );
    }

    /**
     * Get the message content definition.
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
