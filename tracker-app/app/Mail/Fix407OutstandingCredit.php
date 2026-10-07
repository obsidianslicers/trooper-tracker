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
 * Reports EventTrooper records the Fix407 legacy-fallback seeder could not resolve.
 *
 * Sent to administrator troopers after Fix407 finishes running, listing the ATTENDED records
 * Fix406 already couldn't resolve via live costume approvals / membership, and that the legacy
 * (pre-2.0) event_sign_up/costumes tables also couldn't resolve. These require manual review.
 *
 * Queued for asynchronous delivery so the seeder run isn't blocked on mail delivery.
 */
class Fix407OutstandingCredit extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * @param  Trooper  $trooper  The administrator trooper receiving the report.
     * @param  array<int, array{event_trooper_id: int, trooper_name: string, event_name: string, event_id: ?int, costume_name: ?string, reason: string, legacy_note: string}>  $outstanding_rows
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $outstanding_rows)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Fix407: Outstanding Credit Records',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.fix407-outstanding-credit',
            with: [
                'trooper' => $this->trooper,
                'outstanding_rows' => $this->outstanding_rows,
            ],
        );
    }
}
