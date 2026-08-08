<?php

declare(strict_types=1);

namespace App\Mail\Admin\System;

use App\Models\Trooper;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirms to an administrator that the queue worker heartbeat has recovered
 * after a prior SupervisorDown alert.
 *
 * Deliberately not queued (ShouldQueue) - sent synchronously from
 * CheckSupervisorHealthCommand, consistent with SupervisorDown.
 */
class SupervisorRecovered extends Mailable
{
    use SerializesModels;

    public function __construct(private readonly Trooper $trooper) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Queue Worker Recovered',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.supervisor-recovered',
            with: [
                'trooper' => $this->trooper,
            ],
        );
    }
}
