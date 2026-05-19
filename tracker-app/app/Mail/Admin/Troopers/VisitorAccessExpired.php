<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable notifying a visitor trooper that their 6-month access window has expired.
 *
 * Directs the trooper to log in and submit a renewal request to regain access.
 */
class VisitorAccessExpired extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(private readonly Trooper $trooper) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix') . ' Your Visitor Access Has Expired'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.visitor-access-expired',
            with: [
                'trooper' => $this->trooper,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
