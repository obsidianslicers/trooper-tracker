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
 * Mailable for event sign-up confirmation.
 *
 * Sent to troopers when they successfully sign up for an event shift,
 * confirming their registration and providing event details with a calendar link.
 */
class TrooperSignUp extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use HasRetryPolicy;

    /**
     * Create a new event sign-up confirmation email instance.
     *
     * @param  EventTrooper  $event_trooper  The event trooper assignment that was created
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
            subject: config('mail.prefix') . ' Event Sign-Up Confirmation'
        );
    }

    /**
     * Get the message content definition with event data and calendar link.
     *
     * @return Content The email content configuration with view and event data
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.events.trooper-signup',
            with: [
                'event_trooper' => $this->event_trooper,
                'trooper' => $this->event_trooper->trooper,
                'event_shift' => $this->event_trooper->event_shift,
                'event' => $this->event_trooper->event_shift->event,
                'link' => $this->event_trooper->event_shift->createCalendarLink(),
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
