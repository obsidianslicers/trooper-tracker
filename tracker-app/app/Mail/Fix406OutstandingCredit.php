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
 * Reports EventTrooper records the Fix406 backfill seeder could not resolve.
 *
 * Sent to administrator troopers after Fix406 finishes running, listing the
 * ATTENDED records that had no credit source (organization_id and
 * costume_organization_ids both empty) and no eligible organization could be
 * derived from current costume approvals or membership. These require manual
 * review since Fix406 has no reliable way to determine which org to credit.
 *
 * Queued for asynchronous delivery so the seeder run isn't blocked on mail delivery.
 */
class Fix406OutstandingCredit extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * @param  Trooper  $trooper  The administrator trooper receiving the report.
     * @param  array<int, array{event_trooper_id: int, trooper_name: string, event_name: string, event_id: int, costume_name: ?string}>  $outstanding_rows
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
            subject: config('mail.prefix').' Fix406: Outstanding Credit Records',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.fix406-outstanding-credit',
            with: [
                'trooper' => $this->trooper,
                'outstanding_rows' => $this->outstanding_rows,
            ],
        );
    }
}
