<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventUpdatedMail extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Event $event,
        private readonly array $changed_fields,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Event Details Updated'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.events.event-updated',
            with: [
                'event' => $this->event,
                'changed_fields' => $this->changed_fields,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
