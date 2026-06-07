<?php

declare(strict_types=1);

namespace App\Mail\Account;

use App\Mail\HasRetryPolicy;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use HasRetryPolicy;

    public function __construct(public Trooper $trooper)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix') . ' Account Deletion Requested',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account.deletion-requested',
            with: [
                'trooper' => $this->trooper,
                'deletion_date' => $this->trooper->deletion_requested_at?->addDays(30)->toFormattedDateString(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
