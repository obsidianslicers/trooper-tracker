<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TroopersMerged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Trooper $source_trooper,
        public readonly Trooper $target_trooper) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Troopers Merged',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.troopers-merged',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
