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
 * Mailable for trooper status change to "going" notification.
 *
 * Sent to troopers when their event sign-up status changes to GOING,
 * typically when they move from the waitlist to confirmed attendance.
 * Includes a calendar link for the event.
 */
class TrooperNextInLine extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * Create a new trooper next-in-line notification email instance.
     *
     * @param  EventTrooper  $event_trooper  The event trooper assignment that was confirmed
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
            subject: config('mail.prefix').' Event Sign-Up Status Update - GOING'
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
            view: 'emails.events.trooper-nextinline',
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
