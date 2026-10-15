<?php

declare(strict_types=1);

namespace App\Mail\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForumPostCommandStaffMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Event $event,
        private readonly Trooper $poster,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Forum Post — Command Staff Alert'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.events.forum-post-command-staff',
            with: [
                'event' => $this->event,
                'poster' => $this->poster,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
