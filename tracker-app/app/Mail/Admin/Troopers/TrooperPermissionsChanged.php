<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Mail\HasRetryPolicy;
use App\Models\PermissionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a trooper that an approved permission request changed their role or access.
 */
class TrooperPermissionsChanged extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Your Permissions Have Changed'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.trooper-permissions-changed',
            with: [
                'trooper' => $this->permission_request->trooper,
                'changes' => $this->permission_request->changeSummary(),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
