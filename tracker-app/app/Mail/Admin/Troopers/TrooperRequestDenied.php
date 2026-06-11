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
 * Notifies a trooper that their club join request was not approved.
 */
class TrooperRequestDenied extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * @param  TrooperRequest  $trooper_request  The denied TrooperRequest
     */
    public function __construct(private readonly TrooperRequest $trooper_request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Join Request Not Approved — '.$this->trooper_request->organization->name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.trooper-request-denied',
            with: [
                'trooper' => $this->trooper_request->trooper,
                'organization' => $this->trooper_request->organization,
                'denial_reason' => $this->trooper_request->denial_reason,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
