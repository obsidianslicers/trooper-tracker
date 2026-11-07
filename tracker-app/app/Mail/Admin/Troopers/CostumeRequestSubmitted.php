<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Mail\HasRetryPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells an officer that a trooper added a costume waiting for their approval.
 */
class CostumeRequestSubmitted extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $club_names
     */
    public function __construct(
        private readonly string $trooper_name,
        private readonly string $costume_name,
        private readonly array $club_names,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Costume Approval Request'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.costume-request-submitted',
            with: [
                'trooper_name' => $this->trooper_name,
                'costume_name' => $this->costume_name,
                'club_names' => $this->club_names,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
