<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use App\Mail\HasRetryPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for trooper registration confirmation email.
 *
 * Sent to new troopers after they complete the registration process,
 * welcoming them to the Troop Tracker application.
 */
class TrooperRegistered extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * Create a new trooper registration email instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the message envelope with subject line.
     *
     * @return Envelope The email envelope configuration
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Thanks for Registering!',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content The email content configuration with view
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.registered',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
