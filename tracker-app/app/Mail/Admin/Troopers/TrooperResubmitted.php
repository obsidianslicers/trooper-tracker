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

class TrooperResubmitted extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(public readonly Trooper $trooper) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' '.__('emails.Trooper Resubmitted Application'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: email_view('admin.troopers.trooper-resubmitted'),
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
