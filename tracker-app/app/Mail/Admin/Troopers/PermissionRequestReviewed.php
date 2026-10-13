<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Enums\PermissionRequestStatus;
use App\Mail\HasRetryPolicy;
use App\Models\PermissionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the moderator who submitted a permission request that it was approved or denied.
 */
class PermissionRequestReviewed extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function envelope(): Envelope
    {
        $decision = $this->isApproved() ? 'Approved' : 'Denied';

        return new Envelope(
            subject: config('mail.prefix').' Permission Request '.$decision
        );
    }

    public function content(): Content
    {
        $request = $this->permission_request;

        return new Content(
            view: 'emails.admin.troopers.permission-request-reviewed',
            with: [
                'permission_request' => $request,
                'requester' => $request->requester,
                'trooper' => $request->trooper,
                'is_approved' => $this->isApproved(),
                'changes' => $request->changeSummary(),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function isApproved(): bool
    {
        return $this->permission_request->status === PermissionRequestStatus::APPROVED;
    }
}
