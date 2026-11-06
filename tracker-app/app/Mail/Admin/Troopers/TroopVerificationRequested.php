<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells an officer that a flagged trooper's troop is waiting for them to verify it.
 */
class TroopVerificationRequested extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $club_names
     */
    public function __construct(
        private readonly string $trooper_name,
        private readonly string $event_name,
        private readonly ?string $costume_name,
        private readonly array $club_names,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Troop Verification Request'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.troop-verification-requested',
            with: [
                'trooper_name' => $this->trooper_name,
                'event_name' => $this->event_name,
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
