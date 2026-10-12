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
 * Notifies administrators that a moderator has requested permission changes for a trooper.
 */
class PermissionRequestSubmitted extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Permission Request Submitted'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.permission-request-submitted',
            with: [
                'permission_request' => $this->permission_request,
                'trooper' => $this->permission_request->trooper,
                'requester' => $this->permission_request->requester,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
