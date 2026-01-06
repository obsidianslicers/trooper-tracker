<?php

namespace App\Mail\Events;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

class EventShiftComplete extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private readonly EventTrooper $event_trooper)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event Shift - Completed'
        );
    }

    /**
     * Get the message content definition.
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
                'able_status' => Crypt::encryptString(EventTrooperStatus::ATTENDED->value),
                'unable_status' => Crypt::encryptString(EventTrooperStatus::UNABLE_TO_ATTEND->value)
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
}
