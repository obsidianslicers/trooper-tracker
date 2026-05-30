<?php

declare(strict_types=1);

namespace App\Mail\Admin\Events;

use App\Models\EventTrooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRosterActivityMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly string $action,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Event Roster Update'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.events.event-roster-activity',
            with: [
                'event_trooper' => $this->event_trooper,
                'event' => $this->event_trooper->event_shift->event,
                'trooper' => $this->event_trooper->trooper,
                'action' => $this->action,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
