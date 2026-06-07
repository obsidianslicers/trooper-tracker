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
 * Notifies a trooper that their tracker registration was not approved.
 */
class TrooperDenied extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use HasRetryPolicy;

    public function __construct(
        private readonly Trooper $trooper,
        private readonly ?string $denial_reason,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix') . ' Registration Not Approved'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.trooper-denied',
            with: [
                'trooper' => $this->trooper,
                'denial_reason' => $this->denial_reason,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
