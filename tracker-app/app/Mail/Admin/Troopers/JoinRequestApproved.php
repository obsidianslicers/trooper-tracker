<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies a trooper that their club join request was approved.
 */
class JoinRequestApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Trooper  $trooper  The trooper whose request was approved
     * @param  Organization  $organization  The organization they were approved for
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Join Request Approved — '.$this->organization->name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.join-request-approved',
            with: [
                'trooper' => $this->trooper,
                'organization' => $this->organization,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
