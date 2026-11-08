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

class StaleVerifications extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(public readonly int $count) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Troop Verifications Waiting',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.stale-verifications',
        );
    }
}
