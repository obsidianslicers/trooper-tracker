<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventForumPostMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Event $event,
        private readonly string $username,
        private readonly string $post_body,
        private readonly ?string $post_url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' New Forum Reply'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.events.event-forum-post',
            with: [
                'event' => $this->event,
                'username' => $this->username,
                'post_body' => $this->post_body,
                'post_url' => $this->post_url,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
