<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\EventTrooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TentativeStatusReminderMail extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Update Your Tentative Status'
        );
    }

    public function content(): Content
    {
        $event = $this->event_trooper->event_shift->event;

        return new Content(
            view: 'emails.events.tentative-status-reminder',
            with: [
                'event_trooper' => $this->event_trooper,
                'event' => $event,
                'days_until' => (int) now()->diffInDays($event->event_start, false),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
