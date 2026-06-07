<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrooperManualSelectionStandBy extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use HasRetryPolicy;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly Trooper $changed_by,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix') . ' Event Sign-Up Status Update - Changed to STAND BY'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.events.trooper-manual-selection-standby',
            with: [
                'event_trooper' => $this->event_trooper,
                'trooper' => $this->event_trooper->trooper,
                'event_shift' => $this->event_trooper->event_shift,
                'event' => $this->event_trooper->event_shift->event,
                'link' => $this->event_trooper->event_shift->createCalendarLink(),
                'changed_by' => $this->changed_by,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
