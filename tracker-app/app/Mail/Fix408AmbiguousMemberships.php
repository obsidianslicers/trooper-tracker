<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reports ambiguous false-membership candidates Fix408 deliberately left untouched.
 *
 * Fix408 only auto-corrects (trooper, club) pairs where the legacy permission flag says
 * not-a-member but the current tt_trooper_organizations row for that club is already
 * retired/reserve. Pairs where that row still shows "active" are ambiguous — the trooper could
 * have legitimately joined the club for real after the original import — so they're reported here
 * for a human to confirm instead of being auto-corrected.
 *
 * Queued for asynchronous delivery so the seeder run isn't blocked on mail delivery.
 */
class Fix408AmbiguousMemberships extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * @param  Trooper  $trooper  The administrator trooper receiving the report.
     * @param  array<int, array{trooper_id: int, trooper_name: string, organization_name: string, membership_status: string, event_trooper_row_count: int, achievement_row_count: int}>  $ambiguous_memberships
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $ambiguous_memberships)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Fix408: Ambiguous Club Memberships Need Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.fix408-ambiguous-memberships',
            with: [
                'trooper' => $this->trooper,
                'ambiguous_memberships' => $this->ambiguous_memberships,
            ],
        );
    }
}
