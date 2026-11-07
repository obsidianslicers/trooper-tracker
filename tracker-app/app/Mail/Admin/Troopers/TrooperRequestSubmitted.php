<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Mail\HasRetryPolicy;
use App\Models\TrooperRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies moderators that a trooper has submitted a club join request.
 */
class TrooperRequestSubmitted extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * @param  TrooperRequest  $trooper_request  The newly submitted pending TrooperRequest
     */
    public function __construct(private readonly TrooperRequest $trooper_request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Club Join Request Submitted'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.trooper-request-submitted',
            with: [
                'trooper_request' => $this->trooper_request,
                'organization' => $this->trooper_request->organization,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
