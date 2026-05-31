<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies a trooper that their club join request was not approved.
 */
class JoinRequestDenied extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $organization,
        private readonly ?string $denial_reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Join Request Not Approved — '.$this->organization->name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.join-request-denied',
            with: [
                'trooper' => $this->trooper,
                'organization' => $this->organization,
                'denial_reason' => $this->denial_reason,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
