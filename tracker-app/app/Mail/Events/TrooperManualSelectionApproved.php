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

class TrooperManualSelectionApproved extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly Trooper $approved_by,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Event Sign-Up Status Update - Approved to GOING'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.events.trooper-manual-selection-approved',
            with: [
                'event_trooper' => $this->event_trooper,
                'trooper' => $this->event_trooper->trooper,
                'event_shift' => $this->event_trooper->event_shift,
                'event' => $this->event_trooper->event_shift->event,
                'link' => $this->event_trooper->event_shift->createCalendarLink(),
                'approved_by' => $this->approved_by,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
