<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Mail\HasRetryPolicy;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a trooper an officer approved a costume they added for a club.
 */
class CostumeApproved extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Trooper $trooper,
        private readonly string $costume_name,
        private readonly string $club_name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Costume Approved — '.$this->costume_name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.costume-approved',
            with: [
                'trooper' => $this->trooper,
                'costume_name' => $this->costume_name,
                'club_name' => $this->club_name,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
