<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Models\TrooperOrganization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies moderators that a trooper has submitted a club join request.
 */
class TrooperJoinRequestSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  TrooperOrganization  $join_request  The pending TrooperOrganization record
     */
    public function __construct(private readonly TrooperOrganization $join_request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Club Join Request Submitted'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.join-request-submitted',
            with: [
                'join_request' => $this->join_request,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
