<?php

declare(strict_types=1);

namespace App\Mail\Admin\System;

use App\Models\Trooper;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerts an administrator that the queue worker heartbeat has gone stale.
 *
 * Deliberately not queued (ShouldQueue) - sent synchronously from
 * CheckSupervisorHealthCommand, since a queued alert would never be
 * delivered during the exact outage it's reporting on.
 */
class SupervisorDown extends Mailable
{
    use SerializesModels;

    public function __construct(
        private readonly Trooper $trooper,
        private readonly int $minutes_since_last_heartbeat) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Queue Worker Down',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.supervisor-down',
            with: [
                'trooper' => $this->trooper,
                'minutes_since_last_heartbeat' => $this->minutes_since_last_heartbeat,
            ],
        );
    }
}
