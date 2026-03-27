<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for guardian awareness email.
 *
 * Sent to the guardians of minor troopers after they complete the registration process,
 * informing them about the registration and any necessary actions.
 *
 * @implements ShouldQueue
 */
class GuardianAwareness extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new guardian awareness email instance.
     *
     * This mailable does not require dynamic payload data.
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
            subject: config('mail.prefix') . ' Parent/Guardian of Cadet Registration',
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
            view: 'emails.auth.guardian-awareness',
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
