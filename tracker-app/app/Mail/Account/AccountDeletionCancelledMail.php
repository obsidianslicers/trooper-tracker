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

class AccountDeletionCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use HasRetryPolicy;

    public function __construct(public Trooper $trooper)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix') . ' Account Deletion Cancelled',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account.deletion-cancelled',
            with: ['trooper' => $this->trooper],
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
