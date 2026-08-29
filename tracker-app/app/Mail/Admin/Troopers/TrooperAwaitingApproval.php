<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for trooper registration awaiting approval notification.
 *
 * Sent to new troopers after they complete the registration process,
 * informing them that their account is pending administrator approval
 * before they can access the Troop Tracker application.
 */
class TrooperAwaitingApproval extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new awaiting approval email instance.
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
            subject: config('mail.prefix').' Awaiting Approval',
        );
    }

    /**
     * Get the message content definition with awaiting approval view.
     *
     * @return Content The email content configuration with view
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.awaiting-approval',
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
