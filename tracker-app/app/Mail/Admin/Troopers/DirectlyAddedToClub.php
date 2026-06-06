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
 * Notifies a trooper that they have been directly added to a club by a moderator.
 */
class DirectlyAddedToClub extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 60];
    }

    /**
     * @param  Trooper  $trooper  The trooper who was added
     * @param  Organization  $organization  The organization they were added to
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' You\'ve Been Added to '.$this->organization->name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.directly-added-to-club',
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
