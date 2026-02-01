<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for trooper approval notification.
 *
 * Sent to troopers when an administrator approves their account,
 * granting them full access to the Troop Tracker application.
 */
class TrooperApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new trooper approval email instance.
     *
     * @param  Trooper  $trooper  The trooper who was approved
     */
    public function __construct(private readonly Trooper $trooper)
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
            subject: 'Troop Tracker - Welcome, Trooper! You Passed Inspection!'
        );
    }

    /**
     * Get the message content definition with trooper data.
     *
     * @return Content The email content configuration with view and trooper data
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.trooper-approved',
            with: [
                'trooper' => $this->trooper,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
